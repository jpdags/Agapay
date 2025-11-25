<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleLoginController; // Add this line

Route::get('/', function () {
    return view('welcome');
});

// Login Routes
Route::get('/login', function () {
    return view('login');
})->name('login.show');

Route::post('/login', function () {
    return redirect('/dashboard');
})->name('login.submit');

// Google OAuth Routes
Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');

// Signup Routes
Route::get('/signup', function () {
    return view('signup');
})->name('signup.show');

Route::post('/signup', function () {
    return redirect('/dashboard');
})->name('signup.submit');

// Dashboard Routes
Route::get('/dashboard', [DashboardController::class, 'customerDashboard'])->name('dashboard');
Route::get('/dashboard/business', [DashboardController::class, 'businessDashboard'])->name('business.dashboard');

// Switch user type routes
Route::get('/switch-to-business', [DashboardController::class, 'switchToBusiness'])->name('switch.business');
Route::get('/switch-to-customer', [DashboardController::class, 'switchToCustomer'])->name('switch.customer');


Route::get('/create-test-business', function() {
    $user = \App\Models\User::create([
        'name' => 'Test Business Owner',
        'email' => 'business@test.com',
        'password' => bcrypt('password'),
        'user_type' => 0, // Business
    ]);
    
    return "Test business user created!";
});

// Logout Route
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');