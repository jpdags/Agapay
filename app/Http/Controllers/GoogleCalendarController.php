<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Gmail;
use Google_Service_Gmail_Message;
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
        
        // Try to use system certificate store on Windows
        // If php.ini has curl.cainfo set, use that
        $caBundle = ini_get('curl.cainfo');
        if ($caBundle && file_exists($caBundle)) {
            $options['verify'] = $caBundle;
        } elseif (file_exists('C:\php\extras\ssl\cacert.pem')) {
            // Try the default Windows PHP location
            $options['verify'] = 'C:\php\extras\ssl\cacert.pem';
        } else {
            // For development: disable SSL verification (NOT recommended for production)
            // In production, you should download cacert.pem from https://curl.se/ca/cacert.pem
            // and set it in php.ini: curl.cainfo = "C:\path\to\cacert.pem"
            if (config('app.debug', false)) {
                $options['verify'] = false;
                Log::warning('SSL verification disabled for development. This should not be used in production.');
            } else {
                // In production, try to use default system certificates
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
            'https://www.googleapis.com/auth/gmail.send',
        ]);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        
        return $client;
    }

    /**
     * Redirect to Google Calendar authorization
     */
    public function redirect()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $client = $this->getGoogleClient();
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

        $user = Auth::user();
        $client = $this->getGoogleClient();

        if ($request->has('code')) {
            try {
                $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
                
                if (isset($token['error'])) {
                    return redirect()->route('bookings')->with('error', 'Failed to connect Google Calendar: ' . $token['error']);
                }
            } catch (\Exception $e) {
                Log::error('Failed to fetch Google Calendar access token', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return redirect()->route('bookings')->with('error', 'Failed to connect Google Calendar: ' . $e->getMessage());
            }

            // Store the token
            $user->google_calendar_token = json_encode($token);
            $user->save();

            return redirect()->route('bookings')->with('success', 'Google Calendar connected successfully!');
        }

        return redirect()->route('bookings')->with('error', 'Failed to connect Google Calendar.');
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
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            $newToken = $client->getAccessToken();
            $user->google_calendar_token = json_encode($newToken);
            $user->save();
        }

        $service = new Google_Service_Calendar($client);

        // Create event
        $event = new Google_Service_Calendar_Event();
        
        $offeringName = $order->offering_name ?? 'Booking';
        
        // Determine if user is customer or provider
        $isProvider = $order->provider_id === $user->id;
        
        if ($isProvider) {
            $event->setSummary('Agapay Appointment: ' . $offeringName);
            $description = "Appointment #{$order->id}\n";
            $description .= "Customer: " . ($order->customer->name ?? 'Unknown') . "\n";
        } else {
            $event->setSummary('Agapay Booking: ' . $offeringName);
            $description = "Order #{$order->id}\n";
            $description .= "Provider: " . ($order->provider->name ?? 'Unknown') . "\n";
        }
        
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

        // Set end time (1 hour default, or adjust based on offering type)
        $endDateTime = new Google_Service_Calendar_EventDateTime();
        $endDate = clone $startDate;
        $endDate->addHour(); // Default 1 hour duration
        $endDateTime->setDateTime($endDate->toRfc3339String());
        $endDateTime->setTimeZone('Asia/Manila');
        $event->setEnd($endDateTime);

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
            if ($client->isAccessTokenExpired()) {
                $refreshToken = $client->getRefreshToken();
                if ($refreshToken) {
                    $client->refreshToken($refreshToken);
                    $newToken = $client->getAccessToken();
                    $user->google_calendar_token = json_encode($newToken);
                    $user->save();
                } else {
                    Log::warning("Google Calendar token expired and no refresh token available for user {$user->id}");
                    return false;
                }
            }

            $service = new Google_Service_Calendar($client);

            // Create event
            $event = new Google_Service_Calendar_Event();
            
            $offeringName = $order->offering_name ?? 'Booking';
            
            // Determine if user is customer or provider
            $isProvider = $order->provider_id === $user->id;
            
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
     * Get events from Google Calendar
     */
    public function getEvents()
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        if (!$user->google_calendar_token) {
            return response()->json(['events' => []]);
        }

        $client = $this->getGoogleClient();
        $client->setAccessToken(json_decode($user->google_calendar_token, true));

        // Refresh token if expired
        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            $newToken = $client->getAccessToken();
            $user->google_calendar_token = json_encode($newToken);
            $user->save();
        }

        try {
            $service = new Google_Service_Calendar($client);
            $calendarId = 'primary';
            
            // Get events for the next 30 days
            $optParams = [
                'maxResults' => 50,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => now()->toRfc3339String(),
                'timeMax' => now()->addDays(30)->toRfc3339String(),
            ];
            
            $results = $service->events->listEvents($calendarId, $optParams);
            $events = [];
            
            foreach ($results->getItems() as $event) {
                $start = $event->getStart()->getDateTime();
                $end = $event->getEnd()->getDateTime();
                
                $events[] = [
                    'id' => $event->getId(),
                    'summary' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'start' => $start,
                    'end' => $end,
                ];
            }
            
            return response()->json(['events' => $events]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch events: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Send email notification via Gmail API
     */
    public static function sendGmailNotification($to, $subject, $htmlBody, $userToken = null)
    {
        try {
            // If user token is provided, use it; otherwise try to find user by email
            if ($userToken) {
                $token = $userToken;
            } else {
                $user = \App\Models\User::where('email', $to)->first();
                if (!$user || !$user->google_calendar_token) {
                    return false;
                }
                $token = $user->google_calendar_token;
            }

            $controller = new self();
            $client = $controller->getGoogleClient();
            $client->setAccessToken(json_decode($token, true));

            // Refresh token if expired
            if ($client->isAccessTokenExpired()) {
                $refreshToken = $client->getRefreshToken();
                if ($refreshToken) {
                    $client->refreshToken($refreshToken);
                    $newToken = $client->getAccessToken();
                    if (!$userToken) {
                        $user = \App\Models\User::where('email', $to)->first();
                        if ($user) {
                            $user->google_calendar_token = json_encode($newToken);
                            $user->save();
                        }
                    }
                } else {
                    return false;
                }
            }

            // Create Gmail service
            $service = new Google_Service_Gmail($client);

            // Create message
            $message = new Google_Service_Gmail_Message();
            
            // Encode message
            $rawMessage = "To: {$to}\r\n";
            $rawMessage .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $rawMessage .= "Content-Type: text/html; charset=UTF-8\r\n";
            $rawMessage .= "\r\n";
            $rawMessage .= $htmlBody;

            $message->setRaw(base64_encode($rawMessage));

            // Send message
            $service->users_messages->send('me', $message);
            
            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gmail notification failed: ' . $e->getMessage());
            return false;
        }
    }
}
