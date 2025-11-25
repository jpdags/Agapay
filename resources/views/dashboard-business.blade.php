<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agapay - Business Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .bg-dark-red { background-color: #8B0000; }
        .bg-light-red { background-color: #DC2626; }
        .text-dark-red { color: #8B0000; }
        .text-light-red { color: #DC2626; }
        .border-dark-red { border-color: #8B0000; }
        .sidebar { transition: all 0.3s ease; }
        .verified-badge { 
            background: linear-gradient(135deg, #8B0000, #DC2626);
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 bg-white shadow-lg">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-dark-red">Agapay Business</h1>
                <p class="text-sm text-gray-600">Barangay Verified Partner</p>
                <div class="verified-badge px-3 py-1 rounded-full text-xs font-bold mt-2 text-center">
                    ✅ BARANGAY VERIFIED
                </div>
            </div>
            <nav class="mt-6">
                <a href="#" class="flex items-center px-6 py-3 text-gray-700 bg-red-50 border-r-4 border-dark-red">
                    <span class="text-xl mr-4">📊</span>
                    <span class="font-medium">Business Dashboard</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">📦</span>
                    <span class="font-medium">Manage Products</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">🛒</span>
                    <span class="font-medium">Orders</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">⭐</span>
                    <span class="font-medium">Reviews & Ratings</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">💼</span>
                    <span class="font-medium">Business Profile</span>
                </a>
                <a href="{{ route('dashboard') }}" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">👤</span>
                    <span class="font-medium">Customer Mode</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50 w-full text-left">
                        <span class="text-xl mr-4">🚪</span>
                        <span class="font-medium">Logout</span>
                    </button>
                </form>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Business Dashboard</h2>
                        <p class="text-sm text-gray-600">Manage your barangay business</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            📈 {{ $businessStats['customer_rating'] }} Rating
                        </div>
                        <img class="w-8 h-8 rounded-full object-cover" src="https://ui-avatars.com/api/?name=Business&background=8B0000&color=fff" alt="Business">
                    </div>
                </div>
            </header>

            <!-- Business Stats -->
            <main class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <span class="text-xl">📦</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Orders</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $businessStats['total_orders'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-orange-500">
                        <div class="flex items-center">
                            <div class="p-3 bg-orange-100 rounded-lg">
                                <span class="text-xl">⏳</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Pending Orders</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $businessStats['pending_orders'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <span class="text-xl">💰</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Monthly Revenue</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $businessStats['monthly_revenue'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
                        <div class="flex items-center">
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <span class="text-xl">⭐</span>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Customer Rating</p>
                                <p class="text-2xl font-semibold text-gray-900">{{ $businessStats['customer_rating'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders and Popular Items -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Recent Orders -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Orders</h3>
                        <div class="space-y-4">
                            @foreach($recentOrders as $order)
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $order['customer'] }}</p>
                                    <p class="text-sm text-gray-600">{{ $order['item'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-semibold text-dark-red">{{ $order['amount'] }}</p>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $order['status'] == 'delivered' ? 'bg-green-100 text-green-800' : ($order['status'] == 'pending' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                                        {{ $order['status'] }}
                                    </span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Popular Items -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Popular Items</h3>
                        <div class="space-y-4">
                            @foreach($popularItems as $item)
                            <div class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                                    <p class="text-sm text-gray-600">{{ $item['price'] }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-600">{{ $item['orders'] }} orders</p>
                                    <p class="text-sm text-yellow-600">{{ $item['rating'] }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Business Type Section -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Business Categories</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($businessTypes as $key => $type)
                        <div class="border border-gray-200 rounded-lg p-4 text-center hover:shadow-md transition duration-150 cursor-pointer">
                            <div class="text-3xl mb-2">
                                @if($key == 'sari_sari') 🏪
                                @elseif($key == 'lpg_provider') 🔥
                                @elseif($key == 'water_refilling') 💧
                                @elseif($key == 'service_provider') 🔧
                                @endif
                            </div>
                            <p class="font-medium text-gray-800">{{ $type }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Examples for Data Section -->
                <div class="bg-gradient-to-r from-dark-red to-light-red rounded-lg shadow-sm p-6 text-white">
                    <h3 class="text-lg font-semibold mb-4">📋 Examples for Business Data</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-semibold mb-3">LPG Gas Providers:</h4>
                            <div class="space-y-3">
                                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                                    <p class="font-semibold">Aling Nena's Sari-Sari</p>
                                    <p class="text-sm opacity-90">Brands: Shell, Petron | Price: ₱950 | Delivery: ₱30</p>
                                    <p class="text-sm opacity-90">Rating: ★★★★☆ (42 ratings)</p>
                                </div>
                                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                                    <p class="font-semibold">Jun's Gasulahan</p>
                                    <p class="text-sm opacity-90">Brands: PryceGas | Price: ₱930 | Delivery: ₱50</p>
                                    <p class="text-sm opacity-90">Rating: ★★★★☆ (18 ratings)</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-semibold mb-3">Service Providers:</h4>
                            <div class="space-y-3">
                                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                                    <p class="font-semibold">Mang Jun - Verified Electrician</p>
                                    <p class="text-sm opacity-90">Skills: Wiring, Installation | Rate: ₱300 + materials</p>
                                    <p class="text-sm opacity-90">Rating: ★★★★☆ (56 ratings)</p>
                                </div>
                                <div class="bg-white bg-opacity-20 p-3 rounded-lg">
                                    <p class="font-semibold">Lito's Electrical Services</p>
                                    <p class="text-sm opacity-90">Skills: All electrical jobs | Rate: ₱500 service fee</p>
                                    <p class="text-sm opacity-90">Rating: ★★★★☆ (23 ratings)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>