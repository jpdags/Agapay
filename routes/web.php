<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\AuthController;

Route::get('/', function () {
    return view('welcome');
});

// -------------------------
// Login Routes
// -------------------------
Route::get('/login', function () {
    return view('auth.login');
})->name('login.show');

Route::post('/login', function () {
    return redirect('/dashboard');
})->name('login.submit');

// -------------------------
// Signup Routes
// -------------------------
Route::get('/signup', function () {
    return view('auth.signup');
})->name('signup.show');

Route::post('/signup', function () {
    return redirect('/dashboard');
})->name('signup.submit');

// -------------------------
// Mock Google OAuth (For Now)
// -------------------------
Route::get('/auth/google', function () {
    return view('mock-google-auth');
})->name('google.login');

Route::post('/auth/google/callback', function () {

    $testUser = \App\Models\User::firstOrCreate(
        ['email' => 'test@example.com'],
        [
            'name' => 'Test User',
            'password' => bcrypt('password'),
        ]
    );

    Auth::login($testUser);

    return redirect('/dashboard');
})->name('google.callback');

// -------------------------
// Debug: Check users table
// -------------------------
Route::get('/check-table', function () {
    $columns = Schema::getColumnListing('users');
    dd($columns);
});

// -------------------------
// Dashboard (Customer)
// -------------------------
Route::get('/home', function () {
    $userStats = [
        'suki_points' => 0, 
        'total_orders' => 0,
        'pending_orders' => 0,
        'completed_orders' => 0,
    ];

    $recentActivities = [
        ['message' => 'Ordered LPG from Aling Nena', 'time' => '5 mins ago'],
        ['message' => 'Booked Mang Jun (Electrician)', 'time' => '2 hours ago'],
    ];

    $upcomingSchedules = [
        [
            'title' => 'Electrician Visit',
            'description' => 'Outlet repair',
            'date' => '2025-03-10',
            'time' => '3:00 PM'
        ],
    ];

    return view('customer.home', compact('userStats', 'recentActivities', 'upcomingSchedules'));
})->name('home');

// -------------------------
// Categories Page
// -------------------------
Route::get('/categories', function () {
    return view('customer.categories');
})->name('categories');

// -------------------------
// Bookings Page (Customer)
// -------------------------
Route::get('/bookings', function () {
    $bookings = [
        [
            'type' => 'Product',
            'item' => 'LPG Gas Tank (Petron)',
            'provider' => "Aling Nena's Sari-Sari Store",
            'status' => 'Delivered',
            'date' => 'March 10, 2025',
            'time' => '1:00 PM'
        ],
        [
            'type' => 'Service',
            'item' => 'Electrician Visit (Outlet Repair)',
            'provider' => 'Mang Jun',
            'status' => 'Scheduled',
            'date' => 'March 12, 2025',
            'time' => '3:00 PM'
        ],
        [
            'type' => 'Product',
            'item' => 'Water Gallon (5 gal)',
            'provider' => 'Jun Water Refilling',
            'status' => 'Pending',
            'date' => 'March 11, 2025',
            'time' => '10:00 AM'
        ],
    ];

    return view('customer.bookings', compact('bookings'));
})->name('bookings');

// -------------------------
// Settings Page (Customer)
// -------------------------
Route::get('/settings', function () {
    $user = (object)[
        'name' => 'Juan Dela Cruz',
        'email' => 'juan@example.com',
        'phone' => '09123456789',
        'address' => 'Purok 2, Barangay Malinis',
        'barangay' => 'Barangay Malinis',
        'is_verified' => true,
    ];

    return view('customer.settings', compact('user'));
})->name('settings');

// -------------------------
// Dashboard (Provider)
// -------------------------
Route::get('/provider/dashboard', function () {
    $provider = (object)[
        'name' => 'Mang Jun',
        'is_verified' => true
    ];

    $stats = [
        'completed_jobs' => 21,
        'pending_requests' => 4,
        'earnings' => 5200,
    ];

    $requests = [
        (object)[
            'customer_name' => 'Maria Santos',
            'service' => 'Electrical Wiring',
            'schedule' => 'March 11, 2025 - 2:00 PM'
        ],
        (object)[
            'customer_name' => 'Juan Dela Cruz',
            'service' => 'Water Delivery',
            'schedule' => 'March 12, 2025 - 8:00 AM'
        ],
    ];

    return view('provider.dashboard', compact('provider', 'stats', 'requests'));
})->name('dashboard.provider');


// -------------------------
// Provider Pages (Requests, Services, Schedule, Verification)
// -------------------------
Route::get('/provider/requests', function () {
    $provider = (object)[
        'name' => 'Mang Jun',
        'is_verified' => true
    ];

    $requests = [
        (object)[
            'customer' => 'Maria Santos',
            'service' => 'Electrical Wiring',
            'details' => 'Outlet not working',
            'schedule' => 'March 12, 2025 - 3:00 PM'
        ],
        (object)[
            'customer' => 'Juan Dela Cruz',
            'service' => 'Plumbing',
            'details' => 'Leaking pipe under sink',
            'schedule' => 'March 14, 2025 - 9:00 AM'
        ],
    ];

    return view('provider.requests', compact('provider', 'requests'));
})->name('provider.requests');

// -------------------------
// Provider: Services Page
// -------------------------
Route::get('/provider/services', function () {
    $provider = (object)[
        'name' => 'Mang Jun',
        'is_verified' => true
    ];

    $services = [
        (object)[
            'name' => 'Electrical Repair',
            'price' => '₱350 - ₱700',
            'description' => 'Wiring, outlets, breaker issues'
        ],
        (object)[
            'name' => 'Aircon Cleaning',
            'price' => '₱500',
            'description' => 'Deep clean – window or split type'
        ],
    ];

    return view('provider.services', compact('provider', 'services'));
})->name('provider.services');

// -------------------------
// Provider: Schedule Page
// -------------------------
Route::get('/provider/schedule', function () {
    $provider = (object)[
        'name' => 'Mang Jun',
        'is_verified' => true
    ];

    $schedule = [
        (object)[
            'customer' => 'Bong Cruz',
            'service' => 'Aircon Cleaning',
            'date' => 'March 15, 2025',
            'time' => '1:00 PM'
        ],
        (object)[
            'customer' => 'Maria Santos',
            'service' => 'Electrical Wiring',
            'date' => 'March 12, 2025',
            'time' => '3:00 PM'
        ],
    ];

    return view('provider.schedule', compact('provider', 'schedule'));
})->name('provider.schedule');

// -------------------------
// Provider: Verification Page
// -------------------------
Route::get('/provider/verification', function () {
    $provider = (object)[
        'name' => 'Mang Jun',
        'is_verified' => true,
        'barangay' => 'Barangay Malinis',
        'verified_at' => 'Feb 18, 2025',
        'requirements' => [
            'Valid ID (Submitted)',
            'Barangay Certificate (Submitted)',
            'TESDA Certificate (Optional)',
        ],
        'status' => 'Verified'
    ];

    return view('provider.verification', compact('provider'));
})->name('provider.verification');

// -------------------------
// Logout
// -------------------------
Route::post('/logout', function () {
    Auth::logout();
    return redirect('/');
})->name('logout');
