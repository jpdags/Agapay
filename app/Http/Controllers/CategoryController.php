<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Service;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    // Show the main categories selection page
    public function index()
    {
        // If user is logged in, use the customer categories view (works for both customers and providers)
        if (Auth::check()) {
            $user = Auth::user();
            
            // Get user stats (only for customers)
            $userStats = [
                'suki_points' => 0,
                'total_orders' => 0,
                'pending_orders' => 0,
                'completed_orders' => 0,
            ];
            
            // If user is a customer (user_type = 1), calculate stats
            if ($user->user_type === 1) {
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
            }
            
            // Get real data from database for display
            $products = Product::with('user')->get();
            
            // Get reviews for products
            $productReviews = Order::where('offering_type', 'product')
                ->where('status', 'completed')
                ->whereNotNull('rating')
                ->with('customer')
                ->get()
                ->groupBy('offering_id');
            
            // Map products with reviews
            $allProducts = $products->map(function($product) use ($productReviews) {
                $reviews = $productReviews->get($product->id, collect());
                $product->reviews = $reviews->map(function($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at ? $review->created_at->toDateTimeString() : null,
                        'customer' => $review->customer ? [
                            'id' => $review->customer->id,
                            'name' => $review->customer->name,
                        ] : null,
                    ];
                })->values();
                $product->average_rating = $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : 0;
                $product->total_reviews = $reviews->count();
                // Ensure user relationship is loaded with address
                if ($product->user) {
                    $product->user->makeVisible(['address']);
                }
                return $product;
            });
            
            // Get reviews for services
            $serviceReviews = Order::where('offering_type', 'service')
                ->where('status', 'completed')
                ->whereNotNull('rating')
                ->with('customer')
                ->get()
                ->groupBy('offering_id');
            
            $services = Service::with('user')->get()->map(function($service) use ($serviceReviews) {
                $reviews = $serviceReviews->get($service->id, collect());
                $service->reviews = $reviews->map(function($review) {
                    return [
                        'id' => $review->id,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at ? $review->created_at->toDateTimeString() : null,
                        'customer' => $review->customer ? [
                            'id' => $review->customer->id,
                            'name' => $review->customer->name,
                        ] : null,
                    ];
                })->values();
                $service->average_rating = $reviews->isNotEmpty() ? round($reviews->avg('rating'), 1) : 0;
                $service->total_reviews = $reviews->count();
                // Ensure user relationship is loaded with address
                if ($service->user) {
                    $service->user->makeVisible(['address']);
                }
                return $service;
            });
            
            return view('customer.categories', compact('userStats', 'allProducts', 'services'));
        }
        
        // Otherwise use the simple categories view
        return view('categories.index');
    }

    // Show products
    public function products()
    {
        // Real data from database
        $allProducts = Product::with('user')->get();
        return view('categories.products', compact('allProducts'));
    }

    // Show services
    public function services()
    {
        // Real data from database
        $services = Service::with('user')->get();
        return view('categories.services', compact('services'));
    }
}