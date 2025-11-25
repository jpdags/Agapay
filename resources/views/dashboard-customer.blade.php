<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agapay - Customer Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .bg-dark-red { background-color: #8B0000; }
        .bg-light-red { background-color: #DC2626; }
        .text-dark-red { color: #8B0000; }
        .text-light-red { color: #DC2626; }
        .border-dark-red { border-color: #8B0000; }
        .sidebar { transition: all 0.3s ease; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="sidebar w-64 bg-white shadow-lg">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-dark-red">Agapay</h1>
                <p class="text-sm text-gray-600">Suki sa Barangay</p>
            </div>
            <nav class="mt-6">
                <a href="#" class="flex items-center px-6 py-3 text-gray-700 bg-red-50 border-r-4 border-dark-red">
                    <span class="text-xl mr-4">🏠</span>
                    <span class="font-medium">Dashboard</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">🛒</span>
                    <span class="font-medium">Need Goods</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">🔧</span>
                    <span class="font-medium">Need Service</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">💰</span>
                    <span class="font-medium">My Products</span>
                </a>
                <a href="#" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">👷</span>
                    <span class="font-medium">My Services</span>
                </a>
                <a href="{{ route('business.dashboard') }}" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50">
                    <span class="text-xl mr-4">🏢</span>
                    <span class="font-medium">Business Mode</span>
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
                        <h2 class="text-xl font-semibold text-gray-800">Welcome to Agapay!</h2>
                        <p class="text-sm text-gray-600">Your trusted barangay marketplace</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                            🏆 {{ $userStats['suki_points'] }} Suki Points
                        </div>
                        <img class="w-8 h-8 rounded-full object-cover" src="https://ui-avatars.com/api/?name=Customer&background=DC2626&color=fff" alt="Customer">
                    </div>
                </div>
            </header>

            <!-- Quick Actions -->
            <main class="p-6">
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($quickActions as $action)
                        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 hover:border-dark-red transition duration-150 cursor-pointer">
                            <div class="text-3xl mb-3">{{ $action['icon'] }}</div>
                            <h4 class="font-semibold text-gray-800">{{ $action['title'] }}</h4>
                            <p class="text-sm text-gray-600 mt-1">{{ $action['description'] }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Stats and Activities -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Stats -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">My Stats</h3>
                            <div class="space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Total Orders</span>
                                    <span class="font-semibold">{{ $userStats['total_orders'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Pending Orders</span>
                                    <span class="font-semibold text-orange-500">{{ $userStats['pending_orders'] }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-600">Completed</span>
                                    <span class="font-semibold text-green-500">{{ $userStats['completed_orders'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="lg:col-span-2 bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activities</h3>
                        <div class="space-y-3">
                            @foreach($recentActivities as $activity)
                            <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                                <div class="w-2 h-2 mt-2 bg-dark-red rounded-full"></div>
                                <div class="flex-1">
                                    <p class="text-sm text-gray-800">{{ $activity['message'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity['time'] }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- My Listings -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">My Product Listings</h3>
                        <button class="bg-dark-red text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-light-red transition duration-150">
                            + Add New Product
                        </button>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($myListings as $listing)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-150">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-semibold text-gray-800">{{ $listing['title'] }}</h4>
                                <span class="text-sm px-2 py-1 rounded-full {{ $listing['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $listing['status'] }}
                                </span>
                            </div>
                            <p class="text-lg font-bold text-dark-red mb-2">{{ $listing['price'] }}</p>
                            <div class="flex justify-between text-sm text-gray-600">
                                <span>{{ $listing['orders'] }} orders</span>
                                <div class="space-x-2">
                                    <button class="text-blue-600 hover:text-blue-800">Edit</button>
                                    <button class="text-red-600 hover:text-red-800">Delete</button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Examples Section -->
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-blue-800 mb-4">💡 Examples for Your Listings</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-semibold text-blue-700 mb-2">Home-Based Food Ideas:</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Mango Float - ₱250 per tray</li>
                                <li>• Buko Salad - ₱300 per tray</li>
                                <li>• Custom Puto - ₱150 per dozen</li>
                                <li>• Leche Flan - ₱200 per llanera</li>
                                <li>• Pancit Canton - ₱180 per order</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-blue-700 mb-2">Service Ideas:</h4>
                            <ul class="text-sm text-blue-800 space-y-1">
                                <li>• Basic Electrical Repair - ₱300 service fee</li>
                                <li>• Plumbing Services - ₱400 service fee</li>
                                <li>• Tailoring Services - ₱150 per clothing</li>
                                <li>• Tutoring Services - ₱200 per hour</li>
                                <li>• Pet Sitting - ₱150 per day</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>