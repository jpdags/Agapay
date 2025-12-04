<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class GoogleLoginController extends Controller
{
    /**
     * Get HTTP client with proper SSL configuration
     */
    private function getHttpClient()
    {
        $options = [
            'verify' => true, // Enable SSL verification
        ];
        
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
            if (config('app.debug')) {
                $options['verify'] = false;
                Log::warning('SSL verification disabled for development. This should not be used in production.');
            }
        }
        
        return new Client($options);
    }

    public function redirectToGoogle()
    {
        // Check if Google OAuth is configured
        if (empty(config('services.google.client_id')) || empty(config('services.google.client_secret'))) {
            Log::error('Google OAuth not configured - missing client_id or client_secret');
            return redirect('/login')->with('error', 'Google authentication is not configured. Please contact support.');
        }
        
        // Use stateless() to match the callback method
        // This prevents "invalid state" errors
        // Configure HTTP client with proper SSL handling
        try {
            return Socialite::driver('google')
                ->setHttpClient($this->getHttpClient())
                ->stateless()
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Google redirect failed', ['error' => $e->getMessage()]);
            return redirect('/login')->with('error', 'Failed to initiate Google login. Please try again.');
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            Log::info('Google callback started');
            
            // Get the user from Google
            // Using stateless() helps avoid "invalid state" issues that can
            // silently send the user back to the login/signup page.
            // Configure HTTP client with proper SSL handling
            $googleUser = Socialite::driver('google')
                ->setHttpClient($this->getHttpClient())
                ->stateless()
                ->user();
            Log::info('Google user data received', ['email' => $googleUser->getEmail()]);
            
            // Check if the user exists in the database by their email
            $user = User::where('email', $googleUser->getEmail())->first();
            
            // If user exists, log them in and redirect to their dashboard
            if ($user) {
                Log::info('Existing user found: ' . $user->email);
                
                // Update Google ID if not set
                if (!$user->google_id) {
                    $user->update(['google_id' => $googleUser->getId()]);
                }
                
                // Log the user in
                Auth::login($user);
                $request->session()->regenerate();
                Log::info('User logged in successfully');
                
                // Redirect based on the user's existing type
                if ($user->user_type === 0) {
                    return redirect()->route('dashboard.provider');
                }
                return redirect()->route('home');
            }
            
            // New user - store Google user data in session and show role selection
            Log::info('New user, redirecting to role selection');
            $request->session()->put('google_user', [
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
            ]);
            
            return redirect()->route('google.role.select');
            
        } catch (\Exception $e) {
            Log::error('Google OAuth Failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Provide more helpful error messages
            $errorMessage = 'Authentication failed. ';
            if (str_contains($e->getMessage(), 'redirect_uri_mismatch')) {
                $errorMessage .= 'Please check your Google OAuth redirect URI configuration.';
            } elseif (str_contains($e->getMessage(), 'invalid_client')) {
                $errorMessage .= 'Invalid Google OAuth credentials. Please check your .env file.';
            } elseif (str_contains($e->getMessage(), 'cURL error 77') || str_contains($e->getMessage(), 'certificate')) {
                $errorMessage .= 'SSL certificate issue. Please download cacert.pem from https://curl.se/ca/cacert.pem and set curl.cainfo in php.ini, or ensure APP_DEBUG=true in .env for development.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            
            return redirect('/login')->with('error', $errorMessage);
        }
    }

    public function showRoleSelection(Request $request)
    {
        // Ensure we have Google user data in session
        if (!$request->session()->has('google_user')) {
            return redirect()->route('login')->with('error', 'Session expired. Please try again.');
        }

        return view('auth.select-role');
    }

    public function handleRoleSelection(Request $request)
    {
        // Validate role selection
        $validated = $request->validate([
            'user_type' => ['required', 'in:customer,provider'],
        ]);

        // Get Google user data from session
        $googleUserData = $request->session()->get('google_user');
        
        if (!$googleUserData) {
            return redirect()->route('login')->with('error', 'Session expired. Please try again.');
        }

        // Convert user_type: customer = 1, provider = 0
        $userType = $validated['user_type'] === 'customer' ? 1 : 0;

        try {
            // Create the new user
            $user = User::create([
                'name' => $googleUserData['name'],
                'email' => $googleUserData['email'],
                'google_id' => $googleUserData['google_id'],
                'password' => Hash::make(uniqid()),
                'email_verified_at' => now(),
                'user_type' => $userType,
            ]);

            Log::info('New user created via Google OAuth', [
                'email' => $user->email,
                'user_type' => $userType
            ]);

            // Clear the session data
            $request->session()->forget('google_user');

            // Log the user in
            Auth::login($user);
            $request->session()->regenerate();

            // Redirect based on selected role
            if ($userType === 0) {
                return redirect()->route('dashboard.provider');
            }
            return redirect()->route('home');

        } catch (\Exception $e) {
            Log::error('Failed to create user: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to create account. Please try again.']);
        }
    }
}