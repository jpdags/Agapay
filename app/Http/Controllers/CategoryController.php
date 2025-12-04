<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service;
use App\Models\Entrepreneurship;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    // Show the main categories selection page
    public function index()
    {
        // If user is logged in and is a customer (user_type = 1), use customer layout with userStats
        if (Auth::check() && Auth::user()->user_type === 1) {
            $user = Auth::user();
            
            // Get orders for stats
            $orders = Order::where('customer_id', $user->id)->get();
            
            // Update suki_points if needed
            $user->updateSukiPoints();
            $user->refresh();
            
            $userStats = [
                'suki_points' => $user->suki_points ?? $user->calculateSukiPoints(),
                'total_orders' => $orders->count(),
                'pending_orders' => $orders->where('status', 'pending')->count(),
                'completed_orders' => $orders->where('status', 'completed')->count(),
            ];
            
            // Get real data from database for display
            $products = Product::with('user')->get();
            $services = Service::with('user')->get();
            $businesses = Entrepreneurship::with('user')->get();
            
            return view('customer.categories', compact('userStats', 'products', 'services', 'businesses'));
        }
        
        // Otherwise use the simple categories view
        return view('categories.index');
    }

    // Show products
    public function products()
    {
        // Real data from database
        $products = Product::with('user')->get();
        return view('categories.products', compact('products'));
    }

    // Show services
    public function services()
    {
        // Real data from database
        $services = Service::with('user')->get();
        return view('categories.services', compact('services'));
    }

    // Show entrepreneurship / local businesses
    public function entrepreneurship()
    {
        // Real data from database
        $businesses = Entrepreneurship::with('user')->get();
        return view('categories.entrepreneurship', compact('businesses'));
    }
}