<?php

namespace App\Http\Controllers;

use Google_Client;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GmailController extends Controller
{
    /**
     * Encode data for Gmail API (URL-safe base64 without padding)
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get HTTP client with proper SSL configuration
     */
    private function getHttpClient()
    {
        $options = [];
        
        $caBundle = ini_get('curl.cainfo');
        if ($caBundle && file_exists($caBundle)) {
            $options['verify'] = $caBundle;
        } elseif (file_exists('C:\Program Files\php-8.4.15\extras\ssl\cacert.pem')) {
            $options['verify'] = 'C:\Program Files\php-8.4.15\extras\ssl\cacert.pem';
        } elseif (file_exists('C:\php\extras\ssl\cacert.pem')) {
            $options['verify'] = 'C:\php\extras\ssl\cacert.pem';
        } else {
            if (config('app.debug', false)) {
                $options['verify'] = false;
                Log::warning('SSL verification disabled for development. This should not be used in production.');
            } else {
                $options['verify'] = true;
            }
        }
        
        return new Client($options);
    }

    /**
     * Get Google Client with Gmail scope
     */
    private function getGoogleClient()
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setHttpClient($this->getHttpClient());
        
        $redirectUri = config('services.google.calendar_redirect');
        if (!$redirectUri) {
            $baseUrl = config('app.url');
            $port = request()->getPort();
            
            if ($port && $port != 80 && $port != 443) {
                $scheme = request()->getScheme() ?: 'http';
                $host = request()->getHost() ?: parse_url($baseUrl, PHP_URL_HOST) ?: 'localhost';
                $baseUrl = $scheme . '://' . $host . ':' . $port;
            } elseif (strpos($baseUrl, 'localhost') !== false && strpos($baseUrl, ':') === false) {
                $baseUrl = 'http://localhost:8000';
            }
            
            $redirectUri = rtrim($baseUrl, '/') . '/auth/google/calendar/callback';
        }
        
        $client->setRedirectUri($redirectUri);
        $client->setScopes([
            'https://www.googleapis.com/auth/gmail.send',
        ]);
        $client->setAccessType('offline');
        
        return $client;
    }

    /**
     * Refresh Google access token if expired
     * Returns true if successful, false if failed
     * If user is provided, saves the updated token to database
     */
    private function refreshTokenIfNeeded($client, $user = null)
    {
        if (!$client->isAccessTokenExpired()) {
            return true;
        }
        
        $refreshToken = $client->getRefreshToken();
        if (!$refreshToken) {
            if ($user) {
                Log::warning("Gmail token expired and no refresh token available for user {$user->id}. User needs to re-authorize.");
            }
            return false;
        }
        
        try {
            // Ensure the client has the Gmail scope before refreshing
            // This ensures the refreshed token includes the Gmail scope
            $currentScopes = $client->getScopes();
            $gmailScope = 'https://www.googleapis.com/auth/gmail.send';
            if (!in_array($gmailScope, $currentScopes)) {
                $client->addScope($gmailScope);
                Log::info("Added Gmail scope to client before token refresh");
            }
            
            $client->refreshToken($refreshToken);
            $newToken = $client->getAccessToken();
            
            if (!$newToken) {
                throw new \RuntimeException('Token refresh returned empty token');
            }
            
            // Preserve the refresh token if it's not in the new token
            if (!isset($newToken['refresh_token']) && $refreshToken) {
                $newToken['refresh_token'] = $refreshToken;
            }
            
            if ($user) {
                $user->google_calendar_token = json_encode($newToken);
                $user->save();
                Log::info("Gmail token refreshed successfully for user {$user->id}");
            }
            
            return true;
        } catch (\Exception $e) {
            if ($user) {
                Log::error("Failed to refresh Gmail token for user {$user->id}: " . $e->getMessage(), [
                    'class' => get_class($e)
                ]);
            }
            return false;
        }
    }

    /**
     * Check if token has Gmail scope
     */
    private function hasGmailScope($token)
    {
        if (!$token) {
            return false;
        }
        
        $tokenData = is_string($token) ? json_decode($token, true) : $token;
        if (!is_array($tokenData)) {
            return false;
        }
        
        // Check if token has scope information
        if (isset($tokenData['scope'])) {
            $scopes = is_string($tokenData['scope']) ? explode(' ', $tokenData['scope']) : $tokenData['scope'];
            return in_array('https://www.googleapis.com/auth/gmail.send', $scopes);
        }
        
        // If no scope info in token, assume it might have it (token from Calendar auth includes Gmail scope)
        // We'll let the API call determine if it actually works
        return true;
    }

    /**
     * Send email notification via Gmail API
     */
    public static function sendGmailNotification($to, $subject, $htmlBody, $userToken = null)
    {
        $controller = new self();
        $user = null;
        $token = null;

        // Prefer Gmail API when a Google token is available
        if ($userToken) {
            $token = $userToken;
        } else {
            $user = \App\Models\User::where('email', $to)->first();
            if ($user && $user->google_calendar_token) {
                $token = $user->google_calendar_token;
            }
        }

        if ($token) {
            try {
                // Check if token has Gmail scope
                if (!$controller->hasGmailScope($token)) {
                    Log::warning("Token does not have Gmail scope for sending email to {$to}. User may need to re-authorize with Gmail scope.");
                    throw new \RuntimeException('Token missing Gmail scope');
                }

                $client = $controller->getGoogleClient();
                $tokenData = json_decode($token, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException('Invalid token format: ' . json_last_error_msg());
                }
                
                $client->setAccessToken($tokenData);

                // Check if token is expired before attempting refresh
                if ($client->isAccessTokenExpired()) {
                    Log::info("Gmail token expired, attempting refresh for user " . ($user ? $user->id : 'unknown'));
                    
                    if ($controller->refreshTokenIfNeeded($client, $user) === false) {
                        throw new \RuntimeException('Unable to refresh Gmail token. User may need to re-authorize.');
                    }

                    // If token was refreshed and we have a user, update the token variable
                    if ($user) {
                        $token = $user->google_calendar_token;
                        $tokenData = json_decode($token, true);
                        $client->setAccessToken($tokenData);
                    }
                }

                // Verify we still have a valid access token
                $accessToken = $client->getAccessToken();
                if (!$accessToken || (isset($accessToken['error']) && $accessToken['error'])) {
                    throw new \RuntimeException('Invalid access token: ' . ($accessToken['error'] ?? 'Unknown error'));
                }

                // Attempt to send via Gmail API
                $service = new Google_Service_Gmail($client);
                $message = new Google_Service_Gmail_Message();

                // Build the email message in RFC 2822 format
                $rawMessage = "To: {$to}\r\n";
                $rawMessage .= "From: " . config('mail.from.address', 'noreply@agapay.com') . "\r\n";
                $rawMessage .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
                $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
                $rawMessage .= "MIME-Version: 1.0\r\n";
                $rawMessage .= "\r\n";
                $rawMessage .= $htmlBody;

                $message->setRaw($controller->base64UrlEncode($rawMessage));
                
                Log::info("Attempting to send email via Gmail API to {$to}");
                $result = $service->users_messages->send('me', $message);
                
                Log::info("Email sent successfully via Gmail API to {$to}", [
                    'message_id' => $result->getId()
                ]);
                
                return true;
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                
                // Try to parse Google API error response
                $errorDetails = json_decode($errorMessage, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($errorDetails['error']['message'])) {
                    $errorMessage = $errorDetails['error']['message'];
                }
                
                // Check for specific Gmail API errors
                if (stripos($errorMessage, 'insufficient authentication scopes') !== false || 
                    stripos($errorMessage, 'insufficient permission') !== false) {
                    Log::error("Gmail API: Insufficient scopes for {$to}. User needs to re-authorize with Gmail scope. Error: {$errorMessage}");
                } elseif (stripos($errorMessage, 'API has not been used') !== false || 
                          stripos($errorMessage, 'API not enabled') !== false ||
                          (stripos($errorMessage, 'gmail api') !== false && stripos($errorMessage, 'enable') !== false)) {
                    Log::error("Gmail API: API not enabled in Google Cloud Console. Please enable Gmail API in your Google Cloud project. Error: {$errorMessage}");
                } elseif (stripos($errorMessage, 'invalid_grant') !== false) {
                    Log::error("Gmail API: Invalid or expired token for {$to}. User needs to re-authorize. Error: {$errorMessage}");
                } else {
                    Log::error("Gmail API error for {$to}: {$errorMessage}", [
                        'code' => $e->getCode(),
                        'class' => get_class($e),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                // Gmail API failed, will fall through to fallback mailer
            }
        } else {
            Log::info("No Google token available for {$to}, using fallback mailer");
        }

        // Fallback to Laravel mailer when Gmail is unavailable (e.g., no token, API error, etc.)
        try {
            $mailer = config('mail.default', 'log');
            
            // Warn if mailer is set to 'log' - emails won't actually be sent
            if ($mailer === 'log') {
                Log::warning("Laravel mailer is set to 'log' mode. Email to {$to} will only be logged, not actually sent. Configure SMTP in .env (MAIL_MAILER=smtp) to send real emails.");
            }
            
            Log::info("Using Laravel mailer fallback for {$to} (mailer: {$mailer})");
            Mail::html($htmlBody, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            
            if ($mailer === 'log') {
                Log::warning("Email to {$to} was logged but NOT actually sent because MAIL_MAILER is set to 'log'. Check storage/logs/laravel.log for the email content.");
            } else {
                Log::info("Email sent successfully via Laravel mailer ({$mailer}) to {$to}");
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Notification mail fallback failed for {$to}: " . $e->getMessage(), [
                'mailer' => config('mail.default', 'log'),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}