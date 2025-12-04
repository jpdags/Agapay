<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\CategoryController;
use App\Models\User;
use App\Http\Controllers\GoogleLoginController;

Route::get('/', function () {
    return view('welcome');
});

// -------------------------
// Login Routes
// -------------------------
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

    if (Auth::attempt($credentials, $request->boolean('remember-me'))) {
        $request->session()->regenerate();
        
        // Redirect based on user's actual role
        $user = Auth::user();
        if ($user->user_type === 0) {
            return redirect()->route('dashboard.provider');
        }
        return redirect()->route('home');
    }

    return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
})->name('login');

// -------------------------
// Signup Routes
// -------------------------
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
    ]);

    // Convert user_type: customer = 1, provider = 0
    $userType = $validated['user_type'] === 'customer' ? 1 : 0;

    $user = User::create([
        'name' => $validated['firstName'] . ' ' . $validated['lastName'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'user_type' => $userType,
    ]);

    Auth::login($user);

    // Redirect based on role
    if ($userType === 0) {
        return redirect()->route('dashboard.provider');
    }
    return redirect()->route('home');
})->name('signup.submit');

// -------------------------
// Dashboard Route (redirects based on user type)
// -------------------------
Route::get('/dashboard', function () {
    if (!Auth::check()) {
        return redirect('/login');
    }

    $user = Auth::user();
    
    // user_type: 0 = provider, 1 = customer
    if ($user->user_type === 0) {
        return redirect()->route('dashboard.provider');
    } else {
        return redirect()->route('home');
    }
})->name('dashboard')->middleware('auth');

// -------------------------
// Customer Routes
// -------------------------
Route::get('/home', function () {
    // If not authenticated, redirect to login
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();
    
    // Get real orders from database
    $orders = \App\Models\Order::where('customer_id', $user->id)
        ->with('provider')
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Manually load offerings for each order
    foreach ($orders as $order) {
        $order->loadOffering();
    }
    
    // Calculate real stats from orders
    // Update suki_points if needed (in case of data inconsistency)
    $user->updateSukiPoints();
    $user->refresh();
    
    $userStats = [
        'suki_points' => $user->suki_points ?? $user->calculateSukiPoints(),
        'total_orders' => $orders->count(),
        'pending_orders' => $orders->where('status', 'pending')->count(),
        'completed_orders' => $orders->where('status', 'completed')->count(),
    ];

    // Get recent activities from orders (last 5 orders)
    $recentActivities = $orders->take(5)->map(function ($order) {
        $offeringName = $order->offering_name ?? 'Unknown';
        $status = ucfirst($order->status);
        return [
            'message' => "Ordered {$offeringName} - Status: {$status}",
            'time' => $order->created_at->diffForHumans(),
            'order' => $order
        ];
    });

    // Get upcoming schedules (orders with scheduled dates in the future)
    $upcomingSchedules = $orders
        ->filter(function ($order) {
            if (!$order->scheduled_date) {
                return false;
            }
            $scheduledDate = \Carbon\Carbon::parse($order->scheduled_date);
            return $scheduledDate->isFuture() || $scheduledDate->isToday();
        })
        ->sortBy('scheduled_date')
        ->take(5)
        ->map(function ($order) {
            $offeringName = $order->offering_name ?? 'Unknown';
            $date = $order->scheduled_date ? \Carbon\Carbon::parse($order->scheduled_date)->format('M d, Y') : 'Not scheduled';
            $time = $order->scheduled_time ? \Carbon\Carbon::parse($order->scheduled_time)->format('h:i A') : 'Not set';
            return [
                'title' => $offeringName,
                'description' => "Order #{$order->id} - {$order->status}",
                'date' => $date,
                'time' => $time,
                'order' => $order
            ];
        });

    return view('customer.home', [
        'userStats' => $userStats,
        'recentActivities' => $recentActivities,
        'upcomingSchedules' => $upcomingSchedules
    ]);
})->name('home');

Route::get('/bookings', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $user = Auth::user();
    
    // Get real orders from database
    $bookings = \App\Models\Order::where('customer_id', $user->id)
        ->with('provider')
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Manually load offerings for each order
    foreach ($bookings as $booking) {
        $booking->loadOffering();
    }
    
    // Update suki_points if needed
    $user->updateSukiPoints();
    $user->refresh();
    
    $userStats = [
        'suki_points' => $user->suki_points ?? $user->calculateSukiPoints(),
        'total_orders' => $bookings->count(),
        'pending_orders' => $bookings->where('status', 'pending')->count(),
        'completed_orders' => $bookings->where('status', 'completed')->count(),
    ];
    
    return view('customer.bookings', compact('userStats', 'bookings'));
})->name('bookings');

// Create order/booking
Route::post('/order/create', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $customer */
    $customer = Auth::user();
    
    $validated = $request->validate([
        'offering_type' => ['required', 'in:product,service,business'],
        'offering_id' => ['required', 'integer'],
        'provider_id' => ['required', 'integer', 'exists:users,id'],
        'quantity' => ['nullable', 'integer', 'min:1'],
        'scheduled_date' => ['nullable', 'date'],
        'scheduled_time' => ['nullable'],
        'notes' => ['nullable', 'string', 'max:1000'],
        'total_amount' => ['nullable', 'numeric', 'min:0'],
    ]);
    
    // Verify the offering exists and belongs to the provider
    $offering = null;
    if ($validated['offering_type'] === 'product') {
        $offering = \App\Models\Product::where('id', $validated['offering_id'])
            ->where('user_id', $validated['provider_id'])
            ->first();
        if (!$offering) {
            return back()->withErrors(['error' => 'Product not found.']);
        }
        $validated['total_amount'] = ($offering->price * ($validated['quantity'] ?? 1)) + ($offering->delivery_fee ?? 0);
    } elseif ($validated['offering_type'] === 'service') {
        $offering = \App\Models\Service::where('id', $validated['offering_id'])
            ->where('user_id', $validated['provider_id'])
            ->first();
        if (!$offering) {
            return back()->withErrors(['error' => 'Service not found.']);
        }
        $validated['total_amount'] = $offering->rate;
    } elseif ($validated['offering_type'] === 'business') {
        $offering = \App\Models\Entrepreneurship::where('id', $validated['offering_id'])
            ->where('user_id', $validated['provider_id'])
            ->first();
        if (!$offering) {
            return back()->withErrors(['error' => 'Business not found.']);
        }
        $validated['total_amount'] = 0;
    }
    
    // Create the order
    $order = new \App\Models\Order();
    $order->customer_id = $customer->id;
    $order->provider_id = $validated['provider_id'];
    $order->offering_type = $validated['offering_type'];
    $order->offering_id = $validated['offering_id'];
    $order->status = 'pending';
    $order->quantity = $validated['quantity'] ?? 1;
    $order->scheduled_date = $validated['scheduled_date'] ?? null;
    $order->scheduled_time = $validated['scheduled_time'] ? \Carbon\Carbon::parse($validated['scheduled_time'])->format('H:i:s') : null;
    $order->notes = $validated['notes'] ?? null;
    $order->total_amount = $validated['total_amount'] ?? 0;
    $order->save();
    
    return redirect()->route('bookings')->with('success', 'Booking request submitted successfully!');
})->name('order.create');

Route::get('/settings', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $user = Auth::user();
    
    // Get orders for stats
    $orders = \App\Models\Order::where('customer_id', $user->id)->get();
    
    // Update suki_points if needed
    $user->updateSukiPoints();
    $user->refresh();
    
    $userStats = [
        'suki_points' => $user->suki_points ?? $user->calculateSukiPoints(),
        'total_orders' => $orders->count(),
        'pending_orders' => $orders->where('status', 'pending')->count(),
        'completed_orders' => $orders->where('status', 'completed')->count(),
    ];

    return view('customer.settings', compact('userStats', 'user'));
})->name('settings');

Route::post('/settings', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:20'],
        'address' => ['nullable', 'string', 'max:500'],
        'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
    ]);

    // Update user data
    $user->name = $validated['name'];
    $user->phone = $validated['phone'] ?? null;
    $user->address = $validated['address'] ?? null;

    // Handle photo upload
    if ($request->hasFile('photo')) {
        // Delete old photo if exists
        if ($user->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists('photos/' . $user->photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete('photos/' . $user->photo);
        }

        // Store new photo
        $photoPath = $request->file('photo')->store('photos', 'public');
        $user->photo = basename($photoPath);
    }

    $user->save();

    return redirect()->route('settings')->with('success', 'Profile updated successfully!');
})->name('settings.update');

// -------------------------
// Provider/Business Routes
// -------------------------
// Redirect /provider to /provider/dashboard
Route::get('/provider', function () {
    // If not authenticated, redirect to login
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    return redirect()->route('dashboard.provider');
});

Route::get('/provider/dashboard', function () {
    // If not authenticated, redirect to login
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $provider = Auth::user();
    
    // Real data from database - will be 0 if no data exists yet
    $stats = [
        'completed_jobs' => 0, // TODO: Count completed jobs/orders when tables exist
        'pending_requests' => 0, // TODO: Count pending requests when table exists
        'earnings' => 0, // TODO: Calculate from completed orders
    ];

    // Real data from database - empty for now until requests table exists
    $requests = collect([]); // TODO: Get from requests table when created

    return view('provider.dashboard', compact('provider', 'stats', 'requests'));
})->name('dashboard.provider');

Route::get('/provider/requests', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $provider = Auth::user();
    
    // Get real orders/requests from database
    $requests = \App\Models\Order::where('provider_id', $provider->id)
        ->with('customer')
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Manually load offerings for each order
    foreach ($requests as $request) {
        $request->loadOffering();
    }
    
    return view('provider.requests', compact('provider', 'requests'));
})->name('provider.requests');

// Update order status (Accept/Decline)
Route::post('/provider/order/{id}/update-status', function (\Illuminate\Http\Request $request, $id) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $provider */
    $provider = Auth::user();
    
    $order = \App\Models\Order::where('id', $id)
        ->where('provider_id', $provider->id)
        ->firstOrFail();
    
    $validated = $request->validate([
        'status' => ['required', 'in:accepted,declined,completed,cancelled'],
    ]);
    
    $oldStatus = $order->status;
    $order->status = $validated['status'];
    $order->save();
    
    // Award Suki Points when order is completed
    if ($validated['status'] === 'completed' && $oldStatus !== 'completed') {
        $customer = \App\Models\User::find($order->customer_id);
        if ($customer) {
            $customer->updateSukiPoints();
        }
    }
    
    return redirect()->route('provider.requests')->with('success', 'Order status updated successfully!');
})->name('provider.order.update-status');

Route::get('/provider/services', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $provider = Auth::user();
    
    // Real data from database - get all offerings owned by this provider
    $products = \App\Models\Product::where('user_id', $provider->id)->get();
    $services = \App\Models\Service::where('user_id', $provider->id)->get();
    $businesses = \App\Models\Entrepreneurship::where('user_id', $provider->id)->get();
    
    return view('provider.services', compact('provider', 'products', 'services', 'businesses'));
})->name('provider.services');

// Store new offering
Route::post('/provider/offering/store', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $provider */
    $provider = Auth::user();
    $type = $request->input('type');
    
    if ($type === 'product') {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ]);
        
        $product = new \App\Models\Product();
        $product->name = $validated['name'];
        $product->brand = $validated['brand'] ?? null;
        $product->price = $validated['price'];
        $product->delivery_fee = $validated['delivery_fee'] ?? null;
        $product->user_id = $provider->id;
        $product->save();
        
        return redirect()->route('provider.services')->with('success', 'Product added successfully!');
    } elseif ($type === 'service') {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]);
        
        $service = new \App\Models\Service();
        $service->name = $validated['name'];
        $service->description = $validated['description'];
        $service->rate = $validated['rate'];
        $service->user_id = $provider->id;
        $service->save();
        
        return redirect()->route('provider.services')->with('success', 'Service added successfully!');
    } elseif ($type === 'business') {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);

        $business = new \App\Models\Entrepreneurship();
        $business->name = $validated['name'];
        $business->category = $validated['category'];
        $business->contact = $validated['contact'];
        $business->description = $validated['description'];
        $business->user_id = $provider->id;
        $business->save();
        
        return redirect()->route('provider.services')->with('success', 'Business added successfully!');
    }
    
    return redirect()->route('provider.services')->withErrors(['error' => 'Invalid offering type']);
})->name('provider.offering.store');

// Update existing offering
Route::put('/provider/offering/{type}/{id}/update', function (\Illuminate\Http\Request $request, $type, $id) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $provider */
    $provider = Auth::user();
    
    if ($type === 'product') {
        $product = \App\Models\Product::where('id', $id)->where('user_id', $provider->id)->firstOrFail();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
        ]);
        
        $product->name = $validated['name'];
        $product->brand = $validated['brand'] ?? null;
        $product->price = $validated['price'];
        $product->delivery_fee = $validated['delivery_fee'] ?? null;
        $product->save();
        
        return redirect()->route('provider.services')->with('success', 'Product updated successfully!');
    } elseif ($type === 'service') {
        $service = \App\Models\Service::where('id', $id)->where('user_id', $provider->id)->firstOrFail();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'rate' => ['required', 'numeric', 'min:0'],
        ]);
        
        $service->name = $validated['name'];
        $service->description = $validated['description'];
        $service->rate = $validated['rate'];
        $service->save();
        
        return redirect()->route('provider.services')->with('success', 'Service updated successfully!');
    } elseif ($type === 'business') {
        $business = \App\Models\Entrepreneurship::where('id', $id)->where('user_id', $provider->id)->firstOrFail();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'contact' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
        ]);
        
        $business->name = $validated['name'];
        $business->category = $validated['category'];
        $business->contact = $validated['contact'];
        $business->description = $validated['description'];
        $business->save();
        
        return redirect()->route('provider.services')->with('success', 'Business updated successfully!');
    }
    
    return redirect()->route('provider.services')->withErrors(['error' => 'Invalid offering type']);
})->name('provider.offering.update');

// Delete offering
Route::delete('/provider/offering/{type}/{id}/delete', function ($type, $id) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $provider */
    $provider = Auth::user();
    
    if ($type === 'product') {
        $product = \App\Models\Product::where('id', $id)->where('user_id', $provider->id)->firstOrFail();
        $product->delete();
        return redirect()->route('provider.services')->with('success', 'Product deleted successfully!');
    } elseif ($type === 'service') {
        $service = \App\Models\Service::where('id', $id)->where('user_id', $provider->id)->firstOrFail();
        $service->delete();
        return redirect()->route('provider.services')->with('success', 'Service deleted successfully!');
    } elseif ($type === 'business') {
        $business = \App\Models\Entrepreneurship::where('id', $id)->where('user_id', $provider->id)->firstOrFail();
        $business->delete();
        return redirect()->route('provider.services')->with('success', 'Business deleted successfully!');
    }
    
    return redirect()->route('provider.services')->withErrors(['error' => 'Invalid offering type']);
})->name('provider.offering.delete');

Route::get('/provider/schedule', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $provider = Auth::user();
    
    // Real data from database - empty for now until schedules/bookings table exists
    $schedule = collect([]); // TODO: Get scheduled jobs for this provider when table exists
    
    return view('provider.schedule', compact('provider', 'schedule'));
})->name('provider.schedule');

Route::get('/provider/verification', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    /** @var \App\Models\User $provider */
    $provider = Auth::user();
    
    return view('provider.verification', compact('provider'));
})->name('provider.verification');

Route::post('/provider/verification', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    /** @var \App\Models\User $provider */
    $provider = Auth::user();

    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'phone' => ['nullable', 'string', 'max:20'],
        'address' => ['nullable', 'string', 'max:500'],
        'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
    ]);

    // Update provider data
    $provider->name = $validated['name'];
    $provider->phone = $validated['phone'] ?? null;
    $provider->address = $validated['address'] ?? null;

    // Handle photo upload
    if ($request->hasFile('photo')) {
        // Delete old photo if exists
        if ($provider->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists('photos/' . $provider->photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete('photos/' . $provider->photo);
        }

        // Store new photo
        $photoPath = $request->file('photo')->store('photos', 'public');
        $provider->photo = basename($photoPath);
    }

    $provider->save();

    return redirect()->route('provider.verification')->with('success', 'Profile updated successfully!');
})->name('provider.verification.update');

// -------------------------
// Google OAuth Routes
// -------------------------
Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/auth/google/select-role', [GoogleLoginController::class, 'showRoleSelection'])->name('google.role.select');
Route::post('/auth/google/select-role', [GoogleLoginController::class, 'handleRoleSelection'])->name('google.role.submit');


// -------------------------
// Categories Page (Public)
// -------------------------
Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
Route::get('/products', [CategoryController::class, 'products'])->name('categories.products');
Route::get('/services', [CategoryController::class, 'services'])->name('categories.services');
Route::get('/entrepreneurship', [CategoryController::class, 'entrepreneurship'])->name('categories.entrepreneurship');

// -------------------------
// Logout
// -------------------------
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout')->middleware('auth');
