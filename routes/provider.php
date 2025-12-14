<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\GoogleCalendarController;

// Redirect /provider to /provider/dashboard
Route::get('/provider', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    return redirect()->route('dashboard.provider');
});

Route::get('/provider/dashboard', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $provider = Auth::user();
    
    $orders = \App\Models\Order::where('provider_id', $provider->id)
        ->with('customer')
        ->orderBy('created_at', 'desc')
        ->get();
    
    foreach ($orders as $order) {
        $order->loadOffering();
    }
    
    $stats = [
        'completed_jobs' => $orders->where('status', 'completed')->count(),
        'pending_requests' => $orders->where('status', 'pending')->count(),
        'accepted_requests' => $orders->where('status', 'accepted')->count(),
        'earnings' => $orders->where('status', 'completed')->sum('total_amount'),
        'total_sales' => $orders->where('status', 'completed')->sum('quantity') ?: $orders->where('status', 'completed')->count(),
        'total_orders' => $orders->count(),
    ];
    
    $completedOrdersWithRatings = $orders->where('status', 'completed')->whereNotNull('rating');
    $stats['average_rating'] = $completedOrdersWithRatings->count() > 0 
        ? round($completedOrdersWithRatings->avg('rating'), 1) 
        : 0;
    $stats['total_reviews'] = $completedOrdersWithRatings->count();
    
    $recentOrders = $orders->take(5);
    
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
    
    $requests = \App\Models\Order::where('provider_id', $provider->id)
        ->with('customer')
        ->orderBy('created_at', 'desc')
        ->get();
    
    foreach ($requests as $request) {
        $request->loadOffering();
    }
    
    return view('provider.requests', compact('provider', 'requests'));
})->name('provider.requests');

// Update order status
Route::post('/provider/order/{id}/update-status', function (\Illuminate\Http\Request $request, $id) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $provider */
    $provider = Auth::user();
    
    if (!$provider->phone || !$provider->address) {
        return redirect()->route('provider.settings')->withErrors([
            'profile_incomplete' => 'Please complete your profile information (phone and address) in settings before managing orders. This information is required for transactions.'
        ]);
    }
    
    $order = \App\Models\Order::where('id', $id)
        ->where('provider_id', $provider->id)
        ->firstOrFail();
    
    $validated = $request->validate([
        'status' => ['required', 'in:accepted,declined'],
    ]);
    
    $order->status = $validated['status'];
    $order->save();
    
    $order->load(['customer', 'provider']);
    $order->loadOffering();
    
    if ($validated['status'] === 'accepted' && $order->scheduled_date) {
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
        $customer = $order->customer;
        if ($customer->email && $customer->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => $validated['status'],
                'recipientName' => $customer->name
            ])->render();
            
            GmailController::sendGmailNotification(
                $customer->email,
                'Booking ' . ucfirst($validated['status']) . ' - Agapay',
                $htmlBody,
                $customer->google_calendar_token
            );
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send status update notification: ' . $e->getMessage());
    }
    
    try {
        $provider = $order->provider;
        if ($provider->email && $provider->notifications_enabled) {
            $htmlBody = view('emails.booking-notification', [
                'order' => $order,
                'type' => $validated['status'],
                'recipientName' => $provider->name
            ])->render();
            
            GmailController::sendGmailNotification(
                $provider->email,
                'Booking Status Updated: ' . ucfirst($validated['status']) . ' - Agapay',
                $htmlBody,
                $provider->google_calendar_token
            );
        }
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Failed to send status update notification to provider: ' . $e->getMessage());
    }
    
    return redirect()->route('provider.requests')->with('success', 'Order status updated successfully!');
})->name('provider.order.update-status');

Route::get('/provider/services', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $provider = Auth::user();
    
    $products = \App\Models\Product::where('user_id', $provider->id)->get();
    $services = \App\Models\Service::where('user_id', $provider->id)->get();
    
    return view('provider.services', compact('provider', 'products', 'services'));
})->name('provider.services');

// Store new offering
Route::post('/provider/offering/store', function (\Illuminate\Http\Request $request) {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    
    /** @var \App\Models\User $provider */
    $provider = Auth::user();
    
    if (!$provider->phone || !$provider->address) {
        return redirect()->route('provider.settings')->withErrors([
            'profile_incomplete' => 'Please complete your profile information (phone and address) in settings before adding offerings. This information is required for customers to see your location.'
        ]);
    }
    
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
    
    if (!$provider->phone || !$provider->address) {
        return redirect()->route('provider.settings')->withErrors([
            'profile_incomplete' => 'Please complete your profile information (phone and address) in settings before updating offerings. This information is required for customers to see your location.'
        ]);
    }
    
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
    
    if (!$provider->phone || !$provider->address) {
        return redirect()->route('provider.settings')->withErrors([
            'profile_incomplete' => 'Please complete your profile information (phone and address) in settings before updating offerings. This information is required for customers to see your location.'
        ]);
    }
    
    if ($type === 'product') {
        $product = \App\Models\Product::where('id', $id)->where('user_id', $provider->id)->firstOrFail();
        $product->delete();
        return redirect()->route('provider.services')->with('success', 'Product deleted successfully!');
    } elseif ($type === 'service') {
        $service = \App\Models\Service::where('id', $id)->where('user_id', $provider->id)->firstOrFail();
        $service->delete();
        return redirect()->route('provider.services')->with('success', 'Service deleted successfully!');
    }
    
    return redirect()->route('provider.services')->withErrors(['error' => 'Invalid offering type']);
})->name('provider.offering.delete');

Route::get('/provider/schedule', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    $provider = Auth::user();
    
    $orders = \App\Models\Order::where('provider_id', $provider->id)
        ->whereIn('status', ['pending', 'accepted', 'completed'])
        ->whereNotNull('scheduled_date')
        ->with('customer')
        ->orderBy('scheduled_date', 'asc')
        ->get();
    
    $schedule = $orders->map(function($order) {
        $order->loadOffering();
        return (object)[
            'id' => $order->id,
            'customer' => $order->customer->name ?? 'Customer',
            'customer_address' => $order->customer->address ?? 'Not provided',
            'service' => $order->offering_name ?? 'Service/Product',
            'date' => $order->scheduled_date ? $order->scheduled_date->format('Y-m-d') : null,
            'time' => $order->scheduled_time ?? 'Not set',
            'scheduled_date' => $order->scheduled_date ? $order->scheduled_date->format('Y-m-d') : null,
            'created_at' => $order->created_at ? $order->created_at->format('Y-m-d') : null,
            'status' => $order->status,
        ];
    });
    
    $calendarController = new GoogleCalendarController();
    $calendarEmbedUrl = $calendarController->getCalendarEmbedUrl();
    
    return view('provider.schedule', compact('provider', 'schedule', 'calendarEmbedUrl'));
})->name('provider.schedule');

Route::get('/provider/settings', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }
    /** @var \App\Models\User $provider */
    $provider = Auth::user();
    
    return view('provider.verification', compact('provider'));
})->name('provider.settings');

Route::post('/provider/settings', function (\Illuminate\Http\Request $request) {
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

    $wasIncomplete = empty($provider->phone) || empty($provider->address);

    $provider->name = $validated['name'];
    $provider->phone = $validated['phone'] ?? null;
    $provider->address = $validated['address'] ?? null;

    if ($request->hasFile('photo')) {
        if ($provider->photo && \Illuminate\Support\Facades\Storage::disk('public')->exists('photos/' . $provider->photo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete('photos/' . $provider->photo);
        }

        $photoPath = $request->file('photo')->store('photos', 'public');
        $provider->photo = basename($photoPath);
    }

    $provider->save();

    $isNowComplete = !empty($provider->phone) && !empty($provider->address);
    
    $message = 'Profile updated successfully!';
    if ($wasIncomplete && $isNowComplete) {
        $message = 'Profile updated successfully! Your account is now verified.';
    }

    return redirect()->route('provider.settings')->with('success', $message);
})->name('provider.settings.update');

