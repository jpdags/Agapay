<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service;
use App\Models\Entrepreneurship;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    // Show the main categories selection page
    public function index()
    {
        // If user is logged in and is a customer, use customer layout with userStats
        if (Auth::check() && Auth::user()->user_type === 'customer') {
            $userStats = [
                'suki_points' => 0, 
                'total_orders' => 0,
                'pending_orders' => 0,
                'completed_orders' => 0,
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