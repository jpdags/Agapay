<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\GoogleCalendarController;

// Dashboard Route (redirects based on user type)
Route::get('/dashboard', function () {
    if (!Auth::check()) {
        return redirect('/login');
    }

    $user = Auth::user();
    
    if ($user->user_type === 0) {
        return redirect()->route('dashboard.provider');
    } else {
        return redirect()->route('home');
    }
})->name('dashboard')->middleware('auth');

// Customer Home/Dashboard
Route::get('/home', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    /** @var \App\Models\User $user */
    $user = Auth::user();
    
    $orders = \App\Models\Order::where('customer_id', $user->id)
        ->with('provider')
        ->orderBy('created_at', 'desc')
        ->get();
    
    foreach ($orders as $order) {
        $order->loadOffering();
    }
    
    $user->updateSukiPoints();
    $user->refresh();
    
    $userStats = [
        'suki_points' => $user->suki_points ?? $user->calculateSukiPoints(),
        'total_orders' => $orders->count(),
        'pending_orders' => $orders->where('status', 'pending')->count(),
        'completed_orders' => $orders->where('status', 'completed')->count(),
    ];

    $recentActivities = $orders->take(5)->map(function ($order) {
        $offeringName = $order->offering_name ?? 'Unknown';
        $status = ucfirst($order->status);
        return [
            'message' => "Ordered {$offeringName} - Status: {$status}",
            'time' => $order->created_at->diffForHumans(),
            'order' => $order
        ];
    });

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

// Bookings
Route::get('/bookings', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    /** @var \App\Models\User $user */
    $user = Auth::user();
    
    $bookings = \App\Models\Order::where('customer_id', $user->id)
        ->with('provider')
        ->orderBy('created_at', 'desc')
        ->get();
    
    foreach ($bookings as $booking) {
        $booking->loadOffering();
    }
    
    $user->updateSukiPoints();
    $user->refresh();
    
    $userStats = [
        'suki_points' => $user->suki_points ?? $user->calculateSukiPoints(),
        'total_orders' => $bookings->count(),
        'pending_orders' => $bookings->where('status', 'pending')->count(),
        'completed_orders' => $bookings->where('status', 'completed')->count(),
    ];
    
    $calendarController = new GoogleCalendarController();
    $calendarEmbedUrl = $calendarController->getCalendarEmbedUrl();
    
    return view('customer.bookings', compact('userStats', 'bookings', 'calendarEmbedUrl'));
})->name('bookings');

// Customer marks order as completed
Route::post('/order/{id}/complete', function (\Illuminate\Http\Request $request, $id) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $customer */
    $customer = Auth::user();
    
    $order = \App\Models\Order::where('id', $id)
        ->where('customer_id', $customer->id)
        ->firstOrFail();
    
    if ($order->status !== 'accepted') {
        return back()->withErrors(['error' => 'Only accepted orders can be marked as completed.']);
    }
    
    $order->status = 'completed';
    $order->save();
    
    $order->load(['customer', 'provider']);
    $order->loadOffering();
    
    if ($order->scheduled_date) {
        if ($order->customer->google_calendar_token) {
            try {
                GoogleCalendarController::autoSyncOrderToCalendar($order, $order->customer);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to auto-sync order to customer calendar: ' . $e->getMessage());
            }
        }
        
        if ($order->provider->google_calendar_token) {
            try {
                GoogleCalendarController::autoSyncOrderToCalendar($order, $order->provider);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to auto-sync order to provider calendar: ' . $e->getMessage());
            }
        }
    }
    
    try {
        if ($customer->email && $customer->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => 'completed',
                'recipientName' => $customer->name
            ])->render();
            
            GmailController::sendGmailNotification(
                $customer->email,
                'Booking Completed - Agapay',
                $htmlBody,
                $customer->google_calendar_token
            );
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send completion confirmation to customer: ' . $e->getMessage());
    }
    
    try {
        $provider = $order->provider;
        if ($provider && $provider->email && $provider->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => 'completed',
                'recipientName' => $provider->name
            ])->render();
            
            GmailController::sendGmailNotification(
                $provider->email,
                'Booking Completed - Agapay',
                $htmlBody,
                $provider->google_calendar_token
            );
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send completion notification to provider: ' . $e->getMessage());
    }
    
    $customer->updateSukiPoints();
    
    return redirect()->route('bookings')->with('success', 'Order marked as completed.');
})->name('order.complete');

// Create order/booking
Route::post('/order/create', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $customer */
    $customer = Auth::user();
    
    if (!$customer->phone || !$customer->address) {
        return redirect()->route('settings')->withErrors([
            'profile_incomplete' => 'Please complete your profile information (phone and address) in settings before making a booking. This information is required for transactions.'
        ]);
    }
    
    $validated = $request->validate([
        'offering_type' => ['required', 'in:product,service'],
        'offering_id' => ['required', 'integer'],
        'provider_id' => ['required', 'integer', 'exists:users,id'],
        'quantity' => ['nullable', 'integer', 'min:1'],
        'scheduled_date' => ['nullable', 'date', 'after_or_equal:today'],
        'scheduled_time' => ['nullable', 'date_format:H:i', 'required_with:scheduled_date'],
        'notes' => ['nullable', 'string', 'max:1000'],
        'total_amount' => ['nullable', 'numeric', 'min:0'],
    ], [
        'scheduled_date.after_or_equal' => 'The scheduled date cannot be in the past.',
        'scheduled_time.date_format' => 'The time must be in 24-hour HH:MM format.',
    ]);
    
    $offering = null;
    $provider = \App\Models\User::find($validated['provider_id']);
    
    if (!$provider) {
        return back()->withErrors(['error' => 'Provider not found.']);
    }
    
    if (!$provider->phone || !$provider->address) {
        return back()->withErrors([
            'provider_incomplete' => 'This provider has not completed their profile information. Please contact support or choose another provider.'
        ])->withInput();
    }
    
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
    }
    
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
    
    $order->load(['customer', 'provider']);
    $order->loadOffering();
    
    if ($order->scheduled_date && $customer->google_calendar_token) {
        try {
            GoogleCalendarController::autoSyncOrderToCalendar($order, $customer);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to auto-sync order to customer calendar: ' . $e->getMessage());
        }
    }
    
    try {
        $provider = $order->provider;
        if ($provider && $provider->email && $provider->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => 'created',
                'recipientName' => $provider->name
            ])->render();
            
            $emailSent = GmailController::sendGmailNotification(
                $provider->email,
                'New Booking Request - Agapay',
                $htmlBody,
                $provider->google_calendar_token
            );
            
            if (!$emailSent) {
                \Illuminate\Support\Facades\Log::warning("Failed to send booking notification to provider {$provider->email}. Check mail configuration if using fallback mailer.");
            }
        } else {
            if (!$provider) {
                \Illuminate\Support\Facades\Log::warning("Provider not found for order {$order->id}");
            } elseif (!$provider->email) {
                \Illuminate\Support\Facades\Log::warning("Provider {$provider->id} has no email address configured");
            } elseif (!$provider->notifications_enabled) {
                \Illuminate\Support\Facades\Log::info("Provider {$provider->id} has notifications disabled");
            }
        }
        
        if ($order->scheduled_date && $provider && $provider->google_calendar_token) {
            try {
                GoogleCalendarController::autoSyncOrderToCalendar($order, $provider);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to auto-sync order to provider calendar: ' . $e->getMessage());
            }
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send booking notification to provider: ' . $e->getMessage(), [
            'order_id' => $order->id,
            'provider_id' => $order->provider_id,
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    try {
        if ($customer->email && $customer->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => 'created',
                'recipientName' => $customer->name
            ])->render();
            
            GmailController::sendGmailNotification(
                $customer->email,
                'Booking Request Submitted - Agapay',
                $htmlBody,
                $customer->google_calendar_token
            );
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send booking confirmation to customer: ' . $e->getMessage());
    }
    
    return redirect()->route('bookings')->with('success', 'Booking request submitted successfully!');
})->name('order.create');

// Rate order
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

// Settings
Route::get('/settings', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    /** @var \App\Models\User $user */
    $user = Auth::user();
    
    $orders = \App\Models\Order::where('customer_id', $user->id)->get();
    
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

    $wasIncomplete = empty($user->phone) || empty($user->address);

    $user->name = $validated['name'];
    $user->phone = $validated['phone'] ?? null;
    $user->address = $validated['address'] ?? null;

    if ($request->hasFile('photo')) {
        if ($user->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists('photos/' . $user->photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete('photos/' . $user->photo);
        }

        $photoPath = $request->file('photo')->store('photos', 'public');
        $user->photo = basename($photoPath);
    }

    $user->save();

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

    if (!Hash::check($validated['current_password'], $user->password)) {
        return back()->withErrors(['current_password' => 'Current password is incorrect.']);
    }

    $user->password = Hash::make($validated['new_password']);
    $user->save();

    if ($user->notifications_enabled) {
        try {
            $htmlBody = view('emails.password-changed', [
                'userName' => $user->name,
                'userEmail' => $user->email,
                'changedAt' => now()->format('F d, Y g:i A')
            ])->render();
            
            GmailController::sendGmailNotification(
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

    if ($user->notifications_enabled) {
        try {
            $htmlBody = view('emails.notifications-updated', [
                'userName' => $user->name,
                'enabled' => $enabled,
                'updatedAt' => now()->format('F d, Y g:i A')
            ])->render();
            
            GmailController::sendGmailNotification(
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

    if (!Hash::check($validated['password'], $user->password)) {
        return back()->withErrors(['password' => 'Password is incorrect.']);
    }

    $userEmail = $user->email;
    $userName = $user->name;
    $userToken = $user->google_calendar_token;
    $notificationsEnabled = $user->notifications_enabled;

    if ($notificationsEnabled) {
        try {
            $htmlBody = view('emails.account-deleted', [
                'userName' => $userName,
                'userEmail' => $userEmail,
                'deletedAt' => now()->format('F d, Y g:i A')
            ])->render();
            
            GmailController::sendGmailNotification(
                $userEmail,
                'Account Deleted - Agapay',
                $htmlBody,
                $userToken
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send account deletion notification: ' . $e->getMessage());
        }
    }

    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    $user->delete();

    return redirect()->route('login')->with('success', 'Your account has been permanently deleted.');
})->name('account.delete');

