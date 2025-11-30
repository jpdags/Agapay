<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Show the main categories selection page
    public function index()
    {
        return view('categories.index');
    }

    // Show products
    public function products()
    {
        // Example products array; replace with DB query if needed
        $products = [
            ['name' => 'Handmade Basket', 'description' => 'Eco-friendly woven basket', 'price' => 150],
            ['name' => 'Organic Honey', 'description' => 'Locally sourced', 'price' => 250],
            ['name' => 'Herbal Soap', 'description' => 'Natural ingredients', 'price' => 100],
        ];

        return view('categories.products', compact('products'));
    }

    // Show services
    public function services()
    {
        $services = [
            ['name' => 'Plumbing', 'description' => 'Fix leaks and pipe issues', 'price' => 500],
            ['name' => 'Tutoring', 'description' => 'Math and English lessons', 'price' => 300],
            ['name' => 'House Cleaning', 'description' => 'Keep your home spotless', 'price' => 400],
        ];

        return view('categories.services', compact('services'));
    }

    // Show entrepreneurship / local businesses
    public function entrepreneurship()
    {
        $businesses = [
            ['name' => 'Juan\'s Bakery', 'description' => 'Fresh bread daily', 'contact' => '0917-123-4567'],
            ['name' => 'Maria\'s Crafts', 'description' => 'Handmade decorations', 'contact' => '0917-987-6543'],
            ['name' => 'Pedro\'s Carinderia', 'description' => 'Home-cooked meals', 'contact' => '0917-555-1212'],
        ];

        return view('categories.entrepreneurship', compact('businesses'));
    }
}
