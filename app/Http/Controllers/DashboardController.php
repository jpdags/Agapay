<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function customerDashboard()
    {
        // Check if user is customer, if not redirect to business dashboard
        if (auth()->check() && auth()->user()->user_type === 0) {
            return redirect()->route('business.dashboard');
        }

        $userStats = [
            'total_orders' => 15,
            'pending_orders' => 2,
            'completed_orders' => 13,
            'suki_points' => 450
        ];

        $quickActions = [
            ['title' => 'Need Goods', 'description' => 'Order LPG, Water, Essentials', 'icon' => '🛒', 'route' => '#'],
            ['title' => 'Need Service', 'description' => 'Find Electrician, Plumber', 'icon' => '🔧', 'route' => '#'],
            ['title' => 'Sell Items', 'description' => 'List your products', 'icon' => '💰', 'route' => '#'],
            ['title' => 'Offer Service', 'description' => 'Provide your skills', 'icon' => '👷', 'route' => '#'],
        ];

        $recentActivities = [
            ['type' => 'order', 'message' => 'LPG Gas Tank delivered from Aling Nena', 'time' => '2 hours ago'],
            ['type' => 'service', 'message' => 'Mang Jun completed electrical work', 'time' => '1 day ago'],
            ['type' => 'sale', 'message' => 'Your Mango Float order received 5 stars!', 'time' => '2 days ago'],
        ];

        $myListings = [
            ['title' => 'Home-made Mango Float', 'price' => '₱250', 'status' => 'active', 'orders' => 8],
            ['title' => 'Custom Puto', 'price' => '₱150/dozen', 'status' => 'active', 'orders' => 3],
            ['title' => 'Buko Salad', 'price' => '₱300/tray', 'status' => 'inactive', 'orders' => 12],
        ];

        return view('dashboard-customer', compact('userStats', 'quickActions', 'recentActivities', 'myListings'));
    }

    public function businessDashboard()
    {
        // Check if user is business, if not redirect to customer dashboard
        if (auth()->check() && auth()->user()->user_type === 1) {
            return redirect()->route('dashboard');
        }

        $businessStats = [
            'total_orders' => 124,
            'pending_orders' => 8,
            'monthly_revenue' => '₱45,231',
            'customer_rating' => '4.8/5.0'
        ];

        $businessTypes = [
            'sari_sari' => 'Sari-Sari Store',
            'lpg_provider' => 'LPG Gas Provider',
            'water_refilling' => 'Water Refilling',
            'service_provider' => 'Service Provider'
        ];

        $recentOrders = [
            ['customer' => 'Maria Santos', 'item' => 'Shell LPG Gas Tank', 'amount' => '₱950', 'status' => 'delivered'],
            ['customer' => 'Juan dela Cruz', 'item' => 'PryceGas LPG', 'amount' => '₱930', 'status' => 'pending'],
            ['customer' => 'Ana Reyes', 'item' => '5-gallon Water', 'amount' => '₱50', 'status' => 'preparing'],
        ];

        $popularItems = [
            ['name' => 'Shell LPG Gas Tank', 'price' => '₱950', 'orders' => 42, 'rating' => '★★★★☆'],
            ['name' => 'PryceGas LPG', 'price' => '₱930', 'orders' => 38, 'rating' => '★★★★★'],
            ['name' => '5-gallon Water', 'price' => '₱50', 'orders' => 156, 'rating' => '★★★★☆'],
        ];

        return view('dashboard-business', compact('businessStats', 'businessTypes', 'recentOrders', 'popularItems'));
    }

    public function switchToBusiness()
    {
        if (auth()->check()) {
            auth()->user()->update(['user_type' => 0]);
            return redirect()->route('business.dashboard');
        }
        
        return redirect()->route('dashboard');
    }

    public function switchToCustomer()
    {
        if (auth()->check()) {
            auth()->user()->update(['user_type' => 1]);
            return redirect()->route('dashboard');
        }
        
        return redirect()->route('dashboard');
    }
}