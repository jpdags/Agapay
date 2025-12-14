<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GoogleCalendarController extends Controller
{
    /**
     * Get HTTP client with proper SSL configuration
     */
    private function getHttpClient()
    {
        $options = [];
        
        $caBundle = ini_get('curl.cainfo');
        if ($caBundle && file_exists($caBundle)) {
            $options['verify'] = $caBundle;
        } elseif (file_exists('C:\php\extras\ssl\cacert.pem')) {
            // Try the default Windows PHP location
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
     * Get Google Client with Calendar scope
     */
    private function getGoogleClient()
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        
        // Set HTTP client with proper SSL handling
        $client->setHttpClient($this->getHttpClient());
        // Use absolute URL for redirect URI
        $redirectUri = config('services.google.calendar_redirect');
        if (!$redirectUri) {
            // Get the full URL including port if Laravel is running on a specific port
            $baseUrl = config('app.url');
            
            // Try to get port from request if available
            $port = null;
            if (request()->has('HTTP_HOST')) {
                $host = request()->getHost();
                $port = request()->getPort();
            } else {
                // Fallback: check if APP_URL has port, or use request port
                $port = request()->getPort();
            }
            
            // Build base URL with port if needed (and not standard ports)
            if ($port && $port != 80 && $port != 443) {
                $scheme = request()->getScheme() ?: 'http';
                $host = request()->getHost() ?: parse_url($baseUrl, PHP_URL_HOST) ?: 'localhost';
                $baseUrl = $scheme . '://' . $host . ':' . $port;
            } elseif (strpos($baseUrl, 'localhost') !== false && strpos($baseUrl, ':') === false) {
                // If APP_URL is just http://localhost, assume port 8000 for development
                $baseUrl = 'http://localhost:8000';
            }
            
            $redirectUri = rtrim($baseUrl, '/') . '/auth/google/calendar/callback';
        }
        // Log the redirect URI for debugging
        \Illuminate\Support\Facades\Log::info('Google Calendar redirect URI', ['uri' => $redirectUri]);
        $client->setRedirectUri($redirectUri);
        $client->setScopes([
            Google_Service_Calendar::CALENDAR,
            Google_Service_Calendar::CALENDAR_EVENTS,
            'https://www.googleapis.com/auth/gmail.send', // also allow sending email notifications
        ]);
        $client->setAccessType('offline');
        // Don't set prompt here - it will be set in redirect() method with login hint
        
        return $client;
    }

    /**
     * Redirect to Google Calendar authorization
     * If user already has a token, force re-authorization to ensure Gmail scope is included
     */
    public function redirect()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $client = $this->getGoogleClient();
        
        if ($user->email) {
            $client->setLoginHint($user->email);
        }
        
        // If user already has a token, force re-authorization to ensure all scopes (including Gmail) are granted
        // This is important because existing tokens might not have the Gmail scope
        if ($user->google_calendar_token) {
            $client->setPrompt('consent'); // Force consent screen to ensure all scopes are granted
            Log::info("Forcing re-authorization for user {$user->id} to ensure Gmail scope is included");
        }
        
        $authUrl = $client->createAuthUrl();
        
        return redirect($authUrl);
    }

    /**
     * Handle Google Calendar callback
     */
    public function callback(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $client = $this->getGoogleClient();

        // Pick a sensible post-auth redirect based on user role
        $redirectRoute = ($user->user_type === 0) ? 'provider.schedule' : 'bookings';

        if ($request->has('code')) {
            try {
                $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
                
                if (isset($token['error'])) {
                    return redirect()->route($redirectRoute)->with('error', 'Failed to connect Google Calendar: ' . $token['error']);
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch Google Calendar access token', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->route($redirectRoute)->with('error', 'Failed to connect Google Calendar: ' . $e->getMessage());
            }

            // Store the token
            $user->google_calendar_token = json_encode($token);
            $user->save();
            
            // Verify that the token includes Gmail scope
            $tokenData = is_array($token) ? $token : json_decode($token, true);
            $hasGmailScope = false;
            if (isset($tokenData['scope'])) {
                $scopes = is_string($tokenData['scope']) ? explode(' ', $tokenData['scope']) : $tokenData['scope'];
                $hasGmailScope = in_array('https://www.googleapis.com/auth/gmail.send', $scopes);
            }
            
            if ($hasGmailScope) {
                Log::info("User {$user->id} successfully authorized with Gmail scope");
                return redirect()->route($redirectRoute)->with('success', 'Google Calendar and Gmail connected successfully! You will now receive email notifications.');
            } else {
                Log::warning("User {$user->id} authorized but Gmail scope not found in token. They may need to re-authorize.");
                return redirect()->route($redirectRoute)->with('warning', 'Google Calendar connected, but Gmail permissions may be missing. If you don\'t receive email notifications, please reconnect.');
            }
        }

        return redirect()->route($redirectRoute)->with('error', 'Failed to connect Google Calendar.');
    }

    /**
     * Disconnect Google Calendar (and Gmail)
     */
    public function disconnect(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $redirectRoute = ($user->user_type === 0) ? 'provider.schedule' : 'bookings';

        try {
            $user->google_calendar_token = null;
            $user->save();
            
            Log::info("User {$user->id} disconnected Google Calendar");
            
            return redirect()->route($redirectRoute)->with('success', 'Google Calendar disconnected. Reconnect to enable calendar sync and email notifications.');
        } catch (\Exception $e) {
            Log::error('Failed to disconnect Google Calendar: ' . $e->getMessage());
            return redirect()->route($redirectRoute)->with('error', 'Failed to disconnect Google Calendar.');
        }
    }

    /**
     * Sync booking to Google Calendar
     */
    public function syncBooking($orderId)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        if (!$user->google_calendar_token) {
            return response()->json(['error' => 'Google Calendar not connected'], 400);
        }

        // Allow both customers and providers to sync their orders
        $order = \App\Models\Order::where('id', $orderId)
            ->where(function($query) use ($user) {
                $query->where('customer_id', $user->id)
                      ->orWhere('provider_id', $user->id);
            })
            ->firstOrFail();

        if (!$order->scheduled_date) {
            return response()->json(['error' => 'Order has no scheduled date'], 400);
        }

        $client = $this->getGoogleClient();
        $client->setAccessToken(json_decode($user->google_calendar_token, true));

        // Refresh token if expired
        if ($this->refreshTokenIfNeeded($client, $user) === false) {
            return response()->json(['error' => 'Failed to refresh Google Calendar token'], 401);
        }

        // Ensure relationships are loaded
        if (!$order->relationLoaded('customer')) {
            $order->load('customer');
        }
        if (!$order->relationLoaded('provider')) {
            $order->load('provider');
        }
        if (!$order->offering_name) {
            $order->loadOffering();
        }

        $service = new Google_Service_Calendar($client);
        $isProvider = $order->provider_id === $user->id;
        $event = $this->createCalendarEvent($order, $isProvider);

        try {
            $calendarId = 'primary';
            $createdEvent = $service->events->insert($calendarId, $event);
            
            return response()->json([
                'success' => true,
                'event_id' => $createdEvent->getId(),
                'message' => 'Booking synced to Google Calendar successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to sync: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Automatically sync an order to a user's Google Calendar
     * This is called automatically when orders are created or status changes
     */
    public static function autoSyncOrderToCalendar($order, $user)
    {
        // Check if user has Google Calendar connected
        if (!$user->google_calendar_token) {
            return false;
        }

        // Only sync if order has a scheduled date
        if (!$order->scheduled_date) {
            return false;
        }

        try {
            // Ensure relationships are loaded
            if (!$order->relationLoaded('customer')) {
                $order->load('customer');
            }
            if (!$order->relationLoaded('provider')) {
                $order->load('provider');
            }
            if (!$order->offering_name) {
                $order->loadOffering();
            }
            
            $controller = new self();
            $client = $controller->getGoogleClient();
            $client->setAccessToken(json_decode($user->google_calendar_token, true));

            // Refresh token if expired
            if ($controller->refreshTokenIfNeeded($client, $user) === false) {
                return false;
            }

            $service = new Google_Service_Calendar($client);
            $isProvider = $order->provider_id === $user->id;
            $event = $controller->createCalendarEvent($order, $isProvider);

            $calendarId = 'primary';
            $createdEvent = $service->events->insert($calendarId, $event);
            
            Log::info("Order {$order->id} automatically synced to Google Calendar for user {$user->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to auto-sync order {$order->id} to Google Calendar for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get calendar embed URL for the user
     */
    public function getCalendarEmbedUrl()
    {
        if (!Auth::check()) {
            return null;
        }

        $user = Auth::user();
        
        if (!$user->google_calendar_token) {
            return null;
        }

        // Use the user's email as the calendar ID for embed
        // Note: For this to work, the calendar needs to be publicly accessible
        // or the user needs to share it. Alternatively, we can use the API to fetch events.
        $email = urlencode($user->email);
        return "https://calendar.google.com/calendar/embed?src={$email}&ctz=Asia/Manila";
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
                Log::warning("Google Calendar token expired and no refresh token available for user {$user->id}");
            }
            return false;
        }
        
        try {
            $client->refreshToken($refreshToken);
            $newToken = $client->getAccessToken();
            
            // Save updated token if user object is provided
            if ($user) {
                $user->google_calendar_token = json_encode($newToken);
                $user->save();
            }
            
            return true;
        } catch (\Exception $e) {
            if ($user) {
                Log::warning("Failed to refresh Google Calendar token for user {$user->id}: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Create a Google Calendar event from an order
     */
    private function createCalendarEvent($order, $isProvider)
    {
        $event = new Google_Service_Calendar_Event();
        $offeringName = $order->offering_name ?? 'Booking';
        
        if ($isProvider) {
            $event->setSummary('Agapay Appointment: ' . $offeringName);
            $description = "Appointment #{$order->id}\n";
            $description .= "Customer: " . ($order->customer->name ?? 'Unknown') . "\n";
        } else {
            $event->setSummary('Agapay Booking: ' . $offeringName);
            $description = "Order #{$order->id}\n";
            $description .= "Provider: " . ($order->provider->name ?? 'Unknown') . "\n";
        }
        
        $description .= "Status: " . ucfirst($order->status) . "\n";
        
        if ($order->notes) {
            $description .= "Notes: {$order->notes}\n";
        }
        $event->setDescription($description);

        // Set start time
        $startDateTime = new Google_Service_Calendar_EventDateTime();
        $startDate = \Carbon\Carbon::parse($order->scheduled_date);
        if ($order->scheduled_time) {
            $time = \Carbon\Carbon::parse($order->scheduled_time);
            $startDate->setTime($time->hour, $time->minute);
        }
        $startDateTime->setDateTime($startDate->toRfc3339String());
        $startDateTime->setTimeZone('Asia/Manila');
        $event->setStart($startDateTime);

        // Set end time (1 hour default)
        $endDateTime = new Google_Service_Calendar_EventDateTime();
        $endDate = clone $startDate;
        $endDate->addHour();
        $endDateTime->setDateTime($endDate->toRfc3339String());
        $endDateTime->setTimeZone('Asia/Manila');
        $event->setEnd($endDateTime);
        
        return $event;
    }

}
