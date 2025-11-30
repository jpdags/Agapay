@extends('layouts.customer-main')

@section('content')
    <div class="max-w-5xl mx-auto p-6">

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-dark-red">My Bookings</h1>
            <p class="text-gray-600">Track your recent orders and service requests</p>
        </div>

        <!-- Bookings List -->
        <div class="space-y-4">
            @foreach ($bookings as $b)
                <div class="bg-white p-5 rounded-lg shadow border hover:shadow-md transition">
                    <div class="flex justify-between items-start">
                        
                        <div>
                            <!-- Type Badge -->
                            <span class="px-2 py-1 text-xs rounded-full
                                @if($b['type'] === 'Product') bg-blue-100 text-blue-700
                                @else bg-green-100 text-green-700 @endif">
                                {{ $b['type'] }}
                            </span>

                            <h2 class="text-lg font-semibold text-gray-800 mt-2">{{ $b['item'] }}</h2>
                            <p class="text-sm text-gray-600">Provider: <strong>{{ $b['provider'] }}</strong></p>
                            
                            <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm text-dark-red">calendar_month</span>
                                {{ $b['date'] }} — {{ $b['time'] }}
                            </p>
                        </div>

                        <!-- Status -->
                        <span class="px-3 py-1 text-sm rounded-full 
                            @if($b['status'] === 'Delivered' || $b['status'] === 'Completed') bg-green-100 text-green-700
                            @elseif($b['status'] === 'Scheduled') bg-yellow-100 text-yellow-700
                            @else bg-gray-200 text-gray-700 @endif">
                            {{ $b['status'] }}
                        </span>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-4 flex gap-3">
                        @if($b['status'] === 'Pending')
                            <button class="px-4 py-2 bg-dark-red text-white rounded hover:bg-red-800 transition">
                                Contact Provider
                            </button>
                        @endif

                        @if($b['status'] === 'Delivered' || $b['status'] === 'Completed')
                            <button class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">
                                Rate Service
                            </button>
                        @endif

                        @if($b['status'] === 'Scheduled')
                            <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                View Details
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if(count($bookings) === 0)
            <p class="text-center text-gray-500 mt-10">You have no bookings yet.</p>
        @endif

    </div>
@endsection
