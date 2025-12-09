<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\CategoryController;
use App\Models\User;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\GoogleCalendarController;

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

    // Find user by email
    $user = User::where('email', $credentials['email'])->first();
    
    if (!$user) {
        return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
    }
    
    // If user has google_id and password doesn't match, automatically redirect to Google OAuth
    // This handles the case where user registered via Google and tries to log in manually
    if ($user->google_id && !Hash::check($credentials['password'], $user->password)) {
        // Store email and remember me preference in session
        // Note: Since Google OAuth uses stateless(), we'll verify email match in callback
        $request->session()->put('auto_login_email', $credentials['email']);
        $request->session()->put('auto_login_remember', $request->boolean('remember-me'));
        
        // Automatically redirect to Google OAuth
        return redirect()->route('google.redirect');
    }
    
    // Check if user exists and if password is correct
    // Allow login even if user has google_id (they can set a password)
    if (Hash::check($credentials['password'], $user->password)) {
        // Log the user in with remember me option
        Auth::login($user, $request->boolean('remember-me'));
        $request->session()->regenerate();
        
        // Redirect based on user's actual role
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
        'phone' => ['nullable', 'string', 'max:20'],
        'address' => ['nullable', 'string', 'max:500'],
    ]);

    // Convert user_type: customer = 1, provider = 0
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

    // Show warning if optional fields were skipped
    $warning = null;
    if (empty($validated['phone']) || empty($validated['address'])) {
        $warning = 'Please complete your profile information (phone and address) in settings to verify your account.';
    }

    // Redirect based on role
    if ($userType === 0) {
        return redirect()->route('dashboard.provider')->with('warning', $warning);
    }
    return redirect()->route('home')->with('warning', $warning);
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

    /** @var \App\Models\User $user */
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
    /** @var \App\Models\User $user */
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
    
    // Load relationships for email and calendar
    $order->load(['customer', 'provider']);
    $order->loadOffering();
    
    // Automatically sync to Google Calendar for customer (if connected and has scheduled date)
    if ($order->scheduled_date && $customer->google_calendar_token) {
        try {
            \App\Http\Controllers\GoogleCalendarController::autoSyncOrderToCalendar($order, $customer);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to auto-sync order to customer calendar: ' . $e->getMessage());
        }
    }
    
    // Send email notification to provider (only if notifications enabled)
    try {
        $provider = $order->provider;
        if ($provider->email && $provider->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => 'created',
                'recipientName' => $provider->name
            ])->render();
            
            // Try Gmail API first if provider has Google Calendar connected
            if ($provider->google_calendar_token) {
                \App\Http\Controllers\GoogleCalendarController::sendGmailNotification(
                    $provider->email,
                    'New Booking Request - Agapay',
                    $htmlBody,
                    $provider->google_calendar_token
                );
            }
        }
        
        // Automatically sync to Google Calendar for provider (if connected and has scheduled date)
        if ($order->scheduled_date && $provider->google_calendar_token) {
            try {
                \App\Http\Controllers\GoogleCalendarController::autoSyncOrderToCalendar($order, $provider);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to auto-sync order to provider calendar: ' . $e->getMessage());
            }
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send booking notification: ' . $e->getMessage());
    }
    
    // Send email notification to customer (only if notifications enabled)
    try {
        if ($customer->email && $customer->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => 'created',
                'recipientName' => $customer->name
            ])->render();
            
            // Try Gmail API first if customer has Google Calendar connected
            if ($customer->google_calendar_token) {
                \App\Http\Controllers\GoogleCalendarController::sendGmailNotification(
                    $customer->email,
                    'Booking Request Submitted - Agapay',
                    $htmlBody,
                    $customer->google_calendar_token
                );
            }
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send booking confirmation to customer: ' . $e->getMessage());
    }
    
    return redirect()->route('bookings')->with('success', 'Booking request submitted successfully!');
})->name('order.create');

// Rate and comment on completed order
Route::post('/order/{id}/rate', function (\Illuminate\Http\Request $request, $id) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $customer */
    $customer = Auth::user();
    
    $order = \App\Models\Order::where('id', $id)
        ->where('customer_id', $customer->id)
        ->where('status', 'completed')
        ->firstOrFail();
    
    // Check if already rated
    if ($order->rating) {
        return back()->withErrors(['error' => 'You have already rated this order.']);
    }
    
    $validated = $request->validate([
        'rating' => ['required', 'integer', 'min:1', 'max:5'],
        'comment' => ['nullable', 'string', 'max:1000'],
    ]);
    
    $order->rating = $validated['rating'];
    $order->comment = $validated['comment'] ?? null;
    $order->save();
    
    return redirect()->route('bookings')->with('success', 'Thank you for your rating!');
})->name('order.rate');

Route::get('/settings', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    /** @var \App\Models\User $user */
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

    // Check if verification was incomplete before update
    $wasIncomplete = empty($user->phone) || empty($user->address);

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

    // Check if verification is now complete
    $isNowComplete = !empty($user->phone) && !empty($user->address);
    
    $message = 'Profile updated successfully!';
    if ($wasIncomplete && $isNowComplete) {
        $message = 'Profile updated successfully! Your account is now verified.';
    }

    return redirect()->route('settings')->with('success', $message);
})->name('settings.update');

// Change password
Route::post('/password/change', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();

    $validated = $request->validate([
        'current_password' => ['required'],
        'new_password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    // Check current password
    if (!Hash::check($validated['current_password'], $user->password)) {
        return back()->withErrors(['current_password' => 'Current password is incorrect.']);
    }

    // Update password
    $user->password = Hash::make($validated['new_password']);
    $user->save();

    // Send Gmail notification if user has Google Calendar connected
    if ($user->google_calendar_token && $user->notifications_enabled) {
        try {
            $htmlBody = view('emails.password-changed', [
                'userName' => $user->name,
                'userEmail' => $user->email,
                'changedAt' => now()->format('F d, Y g:i A')
            ])->render();
            
            \App\Http\Controllers\GoogleCalendarController::sendGmailNotification(
                $user->email,
                'Password Changed - Agapay',
                $htmlBody,
                $user->google_calendar_token
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send password change notification: ' . $e->getMessage());
        }
    }

    return redirect()->route('settings')->with('success', 'Password updated successfully!');
})->name('password.change');

// Toggle notifications
Route::post('/notifications/toggle', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();

    $enabled = $request->has('enabled') && $request->input('enabled') == '1';
    $user->notifications_enabled = $enabled;
    $user->save();

    // Send Gmail notification if user has Google Calendar connected
    if ($user->google_calendar_token && $user->notifications_enabled) {
        try {
            $htmlBody = view('emails.notifications-updated', [
                'userName' => $user->name,
                'enabled' => $enabled,
                'updatedAt' => now()->format('F d, Y g:i A')
            ])->render();
            
            \App\Http\Controllers\GoogleCalendarController::sendGmailNotification(
                $user->email,
                'Notification Settings Updated - Agapay',
                $htmlBody,
                $user->google_calendar_token
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send notification settings update: ' . $e->getMessage());
        }
    }

    return redirect()->route('settings')->with('success', 'Notification settings updated successfully!');
})->name('notifications.toggle');

// Delete account
Route::delete('/account/delete', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();

    $validated = $request->validate([
        'password' => ['required'],
    ]);

    // Verify password
    if (!Hash::check($validated['password'], $user->password)) {
        return back()->withErrors(['password' => 'Password is incorrect.']);
    }

    // Store user info for email before deletion
    $userEmail = $user->email;
    $userName = $user->name;
    $userToken = $user->google_calendar_token;
    $notificationsEnabled = $user->notifications_enabled;

    // Send Gmail notification before deleting account
    if ($userToken && $notificationsEnabled) {
        try {
            $htmlBody = view('emails.account-deleted', [
                'userName' => $userName,
                'userEmail' => $userEmail,
                'deletedAt' => now()->format('F d, Y g:i A')
            ])->render();
            
            \App\Http\Controllers\GoogleCalendarController::sendGmailNotification(
                $userEmail,
                'Account Deleted - Agapay',
                $htmlBody,
                $userToken
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send account deletion notification: ' . $e->getMessage());
        }
    }

    // Logout user before deletion
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    // Delete user account
    $user->delete();

    return redirect()->route('login')->with('success', 'Your account has been permanently deleted.');
})->name('account.delete');

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
    
    // Get all orders for this provider
    $orders = \App\Models\Order::where('provider_id', $provider->id)
        ->with('customer')
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Manually load offerings for each order
    foreach ($orders as $order) {
        $order->loadOffering();
    }
    
    // Calculate real statistics from orders
    $stats = [
        'completed_jobs' => $orders->where('status', 'completed')->count(),
        'pending_requests' => $orders->where('status', 'pending')->count(),
        'accepted_requests' => $orders->where('status', 'accepted')->count(),
        'earnings' => $orders->where('status', 'completed')->sum('total_amount'),
        'total_sales' => $orders->where('status', 'completed')->sum('quantity') ?: $orders->where('status', 'completed')->count(),
        'total_orders' => $orders->count(),
    ];
    
    // Calculate average rating from completed orders with ratings
    $completedOrdersWithRatings = $orders->where('status', 'completed')->whereNotNull('rating');
    $stats['average_rating'] = $completedOrdersWithRatings->count() > 0 
        ? round($completedOrdersWithRatings->avg('rating'), 1) 
        : 0;
    $stats['total_reviews'] = $completedOrdersWithRatings->count();
    
    // Get recent orders (last 5)
    $recentOrders = $orders->take(5);
    
    // Get orders with reviews/comments (using Collection methods)
    $ordersWithReviews = $orders->where('status', 'completed')
        ->whereNotNull('rating')
        ->sortByDesc('created_at')
        ->take(10);

    return view('provider.dashboard', compact('provider', 'stats', 'recentOrders', 'ordersWithReviews'));
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
    
    // Load relationships for email and calendar
    $order->load(['customer', 'provider']);
    $order->loadOffering();
    
    // Automatically sync to Google Calendar when status changes to accepted or completed
    if (in_array($validated['status'], ['accepted', 'completed']) && $order->scheduled_date) {
        // Sync for customer
        if ($order->customer->google_calendar_token) {
            try {
                \App\Http\Controllers\GoogleCalendarController::autoSyncOrderToCalendar($order, $order->customer);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to auto-sync order to customer calendar: ' . $e->getMessage());
            }
        }
        
        // Sync for provider
        if ($order->provider->google_calendar_token) {
            try {
                \App\Http\Controllers\GoogleCalendarController::autoSyncOrderToCalendar($order, $order->provider);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to auto-sync order to provider calendar: ' . $e->getMessage());
            }
        }
    }
    
    // Send email notification to customer (only if notifications enabled)
    try {
        $customer = $order->customer;
        if ($customer->email && $customer->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => $validated['status'],
                'recipientName' => $customer->name
            ])->render();
            
            // Try Gmail API first if customer has Google Calendar connected
            if ($customer->google_calendar_token) {
                \App\Http\Controllers\GoogleCalendarController::sendGmailNotification(
                    $customer->email,
                    'Booking ' . ucfirst($validated['status']) . ' - Agapay',
                    $htmlBody,
                    $customer->google_calendar_token
                );
            }
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send status update notification: ' . $e->getMessage());
    }
    
    // Send email notification to provider (only if notifications enabled)
    try {
        $provider = $order->provider;
        if ($provider->email && $provider->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => $validated['status'],
                'recipientName' => $provider->name
            ])->render();
            
            // Try Gmail API first if provider has Google Calendar connected
            if ($provider->google_calendar_token) {
                \App\Http\Controllers\GoogleCalendarController::sendGmailNotification(
                    $provider->email,
                    'Booking Status Updated: ' . ucfirst($validated['status']) . ' - Agapay',
                    $htmlBody,
                    $provider->google_calendar_token
                );
            }
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send status update notification to provider: ' . $e->getMessage());
    }
    
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
    
    // Get orders with scheduled dates for this provider (including completed ones)
    $orders = \App\Models\Order::where('provider_id', $provider->id)
        ->whereIn('status', ['pending', 'accepted', 'completed'])
        ->whereNotNull('scheduled_date')
        ->with('customer')
        ->orderBy('scheduled_date', 'asc')
        ->get();
    
    // Format schedule data for the view
    $schedule = $orders->map(function($order) {
        $order->loadOffering();
        return (object)[
            'id' => $order->id,
            'customer' => $order->customer->name ?? 'Customer',
            'service' => $order->offering_name ?? 'Service/Product',
            'date' => $order->scheduled_date ? $order->scheduled_date->format('Y-m-d') : null,
            'time' => $order->scheduled_time ?? 'Not set',
            'scheduled_date' => $order->scheduled_date ? $order->scheduled_date->format('Y-m-d') : null,
            'created_at' => $order->created_at ? $order->created_at->format('Y-m-d') : null,
            'status' => $order->status,
        ];
    });
    
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

    // Check if verification was incomplete before update
    $wasIncomplete = empty($provider->phone) || empty($provider->address);

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

    // Check if verification is now complete
    $isNowComplete = !empty($provider->phone) && !empty($provider->address);
    
    $message = 'Profile updated successfully!';
    if ($wasIncomplete && $isNowComplete) {
        $message = 'Profile updated successfully! Your account is now verified.';
    }

    return redirect()->route('provider.verification')->with('success', $message);
})->name('provider.verification.update');

// -------------------------
// Google OAuth Routes
// -------------------------
Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');
Route::get('/auth/google/select-role', [GoogleLoginController::class, 'showRoleSelection'])->name('google.role.select');
Route::post('/auth/google/select-role', [GoogleLoginController::class, 'handleRoleSelection'])->name('google.role.submit');

// Google Calendar Routes
Route::get('/auth/google/calendar/redirect', [GoogleCalendarController::class, 'redirect'])->name('google.calendar.redirect');
Route::get('/auth/google/calendar/callback', [GoogleCalendarController::class, 'callback'])->name('google.calendar.callback');
Route::post('/calendar/sync/{orderId}', [GoogleCalendarController::class, 'syncBooking'])->name('calendar.sync');
Route::get('/calendar/events', [GoogleCalendarController::class, 'getEvents'])->name('calendar.events');


// -------------------------
// Categories Page (Public)
// -------------------------
Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
Route::get('/products', [CategoryController::class, 'products'])->name('categories.products');
Route::get('/services', [CategoryController::class, 'services'])->name('categories.services');

// -------------------------
// Logout
// -------------------------
Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout')->middleware('auth');
