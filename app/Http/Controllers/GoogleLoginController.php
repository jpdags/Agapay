<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GoogleLoginController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
{
    try {
        \Log::info('Google callback started');
        
        // Test if we can reach Google
        $googleUser = Socialite::driver('google')->stateless()->user();
        \Log::info('Google user data received');
        
        $user = User::where('email', $googleUser->getEmail())->first();
        
        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => Hash::make(uniqid()),
                'email_verified_at' => now(),
                'user_type' => 1,
            ]);
            \Log::info('New user created: ' . $user->email);
        } else {
            \Log::info('Existing user found: ' . $user->email);
            if (!$user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        }
        
        Auth::login($user);
        \Log::info('User logged in successfully');
        
        if ($user->user_type === 0) {
            return redirect()->route('business.dashboard');
        }
        
        return redirect()->route('dashboard');
        
    } catch (\Exception $e) {
        \Log::error('Google OAuth Failed: ' . $e->getMessage());
        return redirect('/login')->with('error', 'Authentication failed: ' . $e->getMessage());
    }
}
}