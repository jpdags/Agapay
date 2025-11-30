@extends('layouts.customer-main')

@section('content')
    <div class="mb-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-dark-red">Home</h1>
            <p class="text-gray-600">Browse and Explore</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Products -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                <span class="material-symbols-outlined text-4xl text-dark-red mb-3">inventory_2</span>
                <h4 class="font-semibold text-gray-800">Products</h4>
                <p class="text-sm text-gray-600 mt-1">Browse barangay-made goods</p>
                <a href="{{ route('categories') }}" class="text-blue-500 hover:text-blue-700">Explore Products</a>
            </div>

            <!-- Services -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                <span class="material-symbols-outlined text-4xl text-dark-red mb-3">handyman</span>
                <h4 class="font-semibold text-gray-800">Services</h4>
                <p class="text-sm text-gray-600 mt-1">Find local helpers & service providers</p>
                <a href="{{ route('categories') }}" class="text-blue-500 hover:text-blue-700">Explore Services</a>
            </div>

            <!-- Entrepreneurship -->
            <div class="bg-white p-6 rounded-lg shadow-sm hover:shadow-md transition">
                <span class="material-symbols-outlined text-4xl text-dark-red mb-3">trending_up</span>
                <h4 class="font-semibold text-gray-800">Entrepreneurship</h4>
                <p class="text-sm text-gray-600 mt-1">Start and grow your barangay business</p>
                <a href="{{ route('categories') }}" class="text-blue-500 hover:text-blue-700">Explore Entrepreneurship</a>
            </div>
        </div>
    </div>

    <!-- Stats + Activities -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- Stats -->
        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-sm">
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
                    <span class="text-gray-600">Completed Orders</span>
                    <span class="font-semibold text-green-500">{{ $userStats['completed_orders'] }}</span>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Activities</h3>
            <div class="space-y-3">
                @foreach($recentActivities as $activity)
                    <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
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

    <!-- Upcoming Schedules -->
    <div class="bg-white p-6 rounded-lg shadow-sm">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Upcoming Schedules</h3>
        </div>
        <div class="space-y-3">
            @foreach($upcomingSchedules as $schedule)
                <div class="flex items-start space-x-4 p-4 border rounded-lg hover:shadow-md transition">
                    <span class="material-symbols-outlined text-3xl text-dark-red">event</span>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-800">{{ $schedule['title'] }}</h4>
                        <p class="text-sm text-gray-600">{{ $schedule['description'] }}</p>
                        <p class="text-xs text-gray-500 mt-1">📅 {{ $schedule['date'] }} • ⏰ {{ $schedule['time'] }}</p>
                    </div>
                </div>
            @endforeach
            @if(count($upcomingSchedules) === 0)
                <p class="text-gray-500 text-center py-4">No upcoming schedules.</p>
            @endif
        </div>
    </div>

@endsection
