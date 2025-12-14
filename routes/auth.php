<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\GoogleLoginController;

// Root route
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Login Routes
Route::get('/login', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return view('auth.login');
})->name('login'); 

Route::post('/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    $user = User::where('email', $credentials['email'])->first();
    
    if (!$user) {
        return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
    }
    
    if ($user->google_id && !Hash::check($credentials['password'], $user->password)) {
        $request->session()->put('auto_login_email', $credentials['email']);
        $request->session()->put('auto_login_remember', $request->boolean('remember-me'));
        return redirect()->route('google.redirect');
    }
    
    if (Hash::check($credentials['password'], $user->password)) {
        Auth::login($user, $request->boolean('remember-me'));
        $request->session()->regenerate();
        
        if ($user->user_type === 0) {
            return redirect()->route('dashboard.provider');
        }
        return redirect()->route('home');
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('login');

// Password Reset Routes
Route::get('/password/reset', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
Route::post('/password/email', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/password/reset/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [PasswordResetController::class, 'reset'])->name('password.update');

// Signup Routes
Route::get('/signup', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return view('auth.signup');
})->name('signup.show');

Route::post('/signup', function (\Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'firstName' => ['required', 'string', 'max:255'],
        'lastName' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'user_type' => ['required', 'in:customer,provider'],
        'phone' => ['nullable', 'string', 'max:20'],
        'address' => ['nullable', 'string', 'max:500'],
    ]);

    $userType = $validated['user_type'] === 'customer' ? 1 : 0;

    $user = User::create([
        'name' => $validated['firstName'] . ' ' . $validated['lastName'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'user_type' => $userType,
        'phone' => $validated['phone'] ?? null,
        'address' => $validated['address'] ?? null,
    ]);

    Auth::login($user);

    $warning = null;
    if (empty($validated['phone']) || empty($validated['address'])) {
        $warning = 'Please complete your profile information (phone and address) in settings to verify your account.';
    }

    if ($userType === 0) {
        return redirect()->route('dashboard.provider')->with('warning', $warning);
    }
    return redirect()->route('home')->with('warning', $warning);
})->name('signup.submit');

// Google OAuth Routes
Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/auth/google/select-role', [GoogleLoginController::class, 'showRoleSelection'])->name('google.role.select');
Route::post('/auth/google/select-role', [GoogleLoginController::class, 'handleRoleSelection'])->name('google.role.submit');

// Logout
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout')->middleware('auth');

