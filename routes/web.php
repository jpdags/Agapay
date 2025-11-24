<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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

// Mock Google OAuth Routes (for testing only)
Route::get('/auth/google', function () {
    // Mock Google OAuth consent screen
    return view('mock-google-auth');
})->name('google.login');
Route::post('/auth/google/callback', function () {
    // Simple test user creation that works with any table structure
    $testUser = \App\Models\User::firstOrCreate(
        ['email' => 'test@example.com'],
        [
            'name' => 'Test User', 
            'password' => bcrypt('password'),
            // Add other columns only if they exist in your table
        ]
    );
    
    Auth::login($testUser);
    return redirect('/dashboard');
})->name('google.callback');

Route::get('/check-table', function () {
    $columns = Schema::getColumnListing('users');
    dd($columns);
});

// Signup Routes
Route::get('/signup', function () {
    return view('signup');
})->name('signup.show');

Route::post('/signup', function () {
    return redirect('/dashboard');
})->name('signup.submit');

// Dashboard Route
Route::get('/dashboard', function () {
    $stats = [
        'total_users' => 1242,
        'revenue' => '$45,231',
        'conversion_rate' => '4.5%',
        'pending_orders' => 28
    ];
    
    $recent_activities = [
        ['user' => 'John Doe', 'action' => 'placed a new order', 'time' => '2 min ago'],
        ['user' => 'Sarah Smith', 'action' => 'updated profile', 'time' => '5 min ago'],
    ];
    
    $chart_data = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        'revenue' => [12000, 19000, 15000, 25000, 22000, 30000],
        'users' => [100, 150, 130, 200, 180, 250]
    ];
    
    return view('dashboard', compact('stats', 'recent_activities', 'chart_data'));
})->name('dashboard');

// Logout Route
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');