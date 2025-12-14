<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Http\Controllers\GmailController;

class PasswordResetController extends Controller
{
    /**
     * Show the password reset request form
     */
    public function showRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle password reset request
     */
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Check if user exists
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Don't reveal if email exists for security
            return back()->with('status', 'If that email address exists in our system, we will send a password reset link.');
        }

        // Generate reset token
        $token = Str::random(64);
        
        // Store token in database (expires in 60 minutes)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Generate reset URL
        $resetUrl = url('/password/reset/' . $token . '?email=' . urlencode($request->email));

        // Send email via Gmail API or mailer (falls back automatically)
        if ($user->notifications_enabled) {
            try {
                $htmlBody = view('emails.password-reset', [
                    'userName' => $user->name,
                    'resetUrl' => $resetUrl,
                    'expiresIn' => '60 minutes',
                ])->render();

                GmailController::sendGmailNotification(
                    $user->email,
                    'Password Reset Request - Agapay',
                    $htmlBody,
                    $user->google_calendar_token
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send password reset email: ' . $e->getMessage());
            }
        }

        return back()->with('status', 'If that email address exists in our system, we will send a password reset link.');
    }

    /**
     * Show the password reset form
     */
    public function showResetForm(Request $request, $token = null)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    /**
     * Handle password reset
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Check if token exists and is valid
        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset) {
            return back()->withErrors(['email' => 'Invalid or expired reset token.']);
        }

        // Check if token is expired (60 minutes)
        if (Carbon::parse($passwordReset->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return back()->withErrors(['email' => 'This password reset token has expired. Please request a new one.']);
        }

        // Verify token
        if (!Hash::check($request->token, $passwordReset->token)) {
            return back()->withErrors(['email' => 'Invalid reset token.']);
        }

        // Find user and update password
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User not found.']);
        }

        // Update password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the reset token
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Send confirmation email (GmailController falls back to mailer)
        if ($user->notifications_enabled) {
            try {
                $htmlBody = view('emails.password-reset-success', [
                    'userName' => $user->name,
                    'resetAt' => now()->format('F d, Y g:i A'),
                ])->render();

                GmailController::sendGmailNotification(
                    $user->email,
                    'Password Reset Successful - Agapay',
                    $htmlBody,
                    $user->google_calendar_token
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send password reset confirmation email: ' . $e->getMessage());
            }
        }

        return redirect()->route('login')->with('status', 'Your password has been reset successfully. You can now log in with your new password.');
    }
}