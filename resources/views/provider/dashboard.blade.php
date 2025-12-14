@extends('layouts.provider-main')

@section('content')
    <div>
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Welcome to your Dashboard</h2>

        @if(session('warning'))
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <p class="font-semibold">⚠️ Important:</p>
                <p>{{ session('warning') }}</p>
            </div>
        @endif

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 shadow rounded-lg">
                <p class="text-sm text-gray-600 mb-2">Completed Jobs</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['completed_jobs'] ?? 0 }}</p>
            </div>

            <div class="bg-white p-6 shadow rounded-lg">
                <p class="text-sm text-gray-600 mb-2">Pending Requests</p>
                <p class="text-2xl font-bold text-orange-500">{{ $stats['pending_requests'] ?? 0 }}</p>
            </div>

            <div class="bg-white p-6 shadow rounded-lg">
                <p class="text-sm text-gray-600 mb-2">Total Earnings</p>
                <p class="text-2xl font-bold text-green-600">₱{{ number_format($stats['earnings'] ?? 0, 2) }}</p>
            </div>

            <div class="bg-white p-6 shadow rounded-lg">
                <p class="text-sm text-gray-600 mb-2">Total Sales</p>
                <p class="text-2xl font-bold text-blue-600">{{ $stats['total_sales'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Ratings and Reviews Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 shadow rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Rating & Reviews</h3>
                <div class="flex items-center mb-4">
                    <div class="text-4xl font-bold text-gray-800 mr-4">
                        {{ $stats['average_rating'] ?? 0 }}
                    </div>
                    <div>
                        <div class="flex items-center mb-1">
                            @for($i = 1; $i <= 5; $i++)
                                <span class="text-2xl {{ $i <= ($stats['average_rating'] ?? 0) ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                            @endfor
                        </div>
                        <p class="text-sm text-gray-600">{{ $stats['total_reviews'] ?? 0 }} {{ $stats['total_reviews'] == 1 ? 'review' : 'reviews' }}</p>
                    </div>
                </div>
                @if(($stats['total_reviews'] ?? 0) > 0)
                    <p class="text-sm text-gray-600">Based on {{ $stats['completed_jobs'] ?? 0 }} completed {{ ($stats['completed_jobs'] ?? 0) == 1 ? 'job' : 'jobs' }}</p>
                @else
                    <p class="text-sm text-gray-500">No reviews yet. Complete jobs to receive reviews!</p>
                @endif
            </div>

            <div class="bg-white p-6 shadow rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Summary</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total Orders</span>
                        <span class="text-lg font-semibold text-gray-800">{{ $stats['total_orders'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Accepted</span>
                        <span class="text-lg font-semibold text-blue-600">{{ $stats['accepted_requests'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Completed</span>
                        <span class="text-lg font-semibold text-green-600">{{ $stats['completed_jobs'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between items-center border-t pt-3">
                        <span class="text-sm font-medium text-gray-700">Total Earnings</span>
                        <span class="text-lg font-bold text-green-600">₱{{ number_format($stats['earnings'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders Section -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-4 text-gray-800">Recent Orders</h3>
            @forelse($recentOrders as $order)
                <div class="bg-white p-6 rounded-lg shadow mb-4">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="text-gray-800 font-semibold">{{ $order->customer->name ?? 'Customer' }}</p>
                            <p class="text-sm text-gray-600 mt-1">
                                @if($order->offering_type === 'product')
                                    Product: {{ $order->offering_name ?? 'Unknown' }}
                                @elseif($order->offering_type === 'service')
                                    Service: {{ $order->offering_name ?? 'Unknown' }}
                                @endif
                            </p>
                            @if($order->scheduled_date)
                                <p class="text-xs text-gray-500 mt-1">
                                    📅 {{ $order->scheduled_date->format('M d, Y') }}
                                    @if($order->scheduled_time)
                                        • ⏰ {{ \Carbon\Carbon::parse($order->scheduled_time)->format('g:i A') }}
                                    @endif
                                </p>
                            @endif
                        </div>
                        <span class="px-3 py-1 text-sm rounded-full 
                            @if($order->status === 'pending') bg-yellow-100 text-yellow-700
                            @elseif($order->status === 'accepted') bg-blue-100 text-blue-700
                            @elseif($order->status === 'completed') bg-green-100 text-green-700
                            @elseif($order->status === 'declined') bg-red-100 text-red-700
                            @else bg-gray-100 text-gray-700 @endif">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                    @if($order->total_amount > 0)
                        <p class="text-sm font-medium text-green-600">Amount: ₱{{ number_format($order->total_amount, 2) }}</p>
                    @endif
            </div>
            @empty
                <div class="bg-white p-6 rounded-lg shadow mb-4">
                    <p class="text-gray-500 text-center">No recent orders.</p>
                </div>
            @endforelse
        </div>

        <!-- Reviews and Comments Section -->
        @if(($stats['total_reviews'] ?? 0) > 0)
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold text-gray-800">Reviews & Comments</h3>
                @if($ordersWithReviews->count() > 3)
                <button onclick="toggleReviews()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    <span id="reviewsToggleText">Show All Reviews</span>
                </button>
                @endif
            </div>
            @if($ordersWithReviews->count() > 3)
            <div id="reviewsSection" class="hidden">
                @foreach($ordersWithReviews as $order)
                    <div class="bg-white p-6 rounded-lg shadow mb-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="text-gray-800 font-semibold">{{ $order->customer->name ?? 'Customer' }}</p>
                                <p class="text-sm text-gray-600 mt-1">
                                    @if($order->offering_type === 'product')
                                        Product: {{ $order->offering_name ?? 'Unknown' }}
                                    @elseif($order->offering_type === 'service')
                                        Service: {{ $order->offering_name ?? 'Unknown' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 mt-1">{{ $order->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-xl {{ $i <= $order->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                @endfor
                                <span class="ml-2 text-sm text-gray-600">({{ $order->rating }}/5)</span>
                            </div>
                        </div>
                        @if($order->comment)
                            <div class="mt-3 p-3 bg-gray-50 rounded border">
                                <p class="text-sm text-gray-700"><strong>Comment:</strong> {{ $order->comment }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            <!-- Show first 3 reviews by default -->
            <div id="reviewsPreview">
                @foreach($ordersWithReviews->take(3) as $order)
                    <div class="bg-white p-6 rounded-lg shadow mb-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="text-gray-800 font-semibold">{{ $order->customer->name ?? 'Customer' }}</p>
                                <p class="text-sm text-gray-600 mt-1">
                                    @if($order->offering_type === 'product')
                                        Product: {{ $order->offering_name ?? 'Unknown' }}
                                    @elseif($order->offering_type === 'service')
                                        Service: {{ $order->offering_name ?? 'Unknown' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 mt-1">{{ $order->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-xl {{ $i <= $order->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                @endfor
                                <span class="ml-2 text-sm text-gray-600">({{ $order->rating }}/5)</span>
                            </div>
                        </div>
                        @if($order->comment)
                            <div class="mt-3 p-3 bg-gray-50 rounded border">
                                <p class="text-sm text-gray-700"><strong>Comment:</strong> {{ $order->comment }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @else
            <!-- Show all reviews if 3 or less -->
            <div>
                @foreach($ordersWithReviews as $order)
                    <div class="bg-white p-6 rounded-lg shadow mb-4">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <p class="text-gray-800 font-semibold">{{ $order->customer->name ?? 'Customer' }}</p>
                                <p class="text-sm text-gray-600 mt-1">
                                    @if($order->offering_type === 'product')
                                        Product: {{ $order->offering_name ?? 'Unknown' }}
                                    @elseif($order->offering_type === 'service')
                                        Service: {{ $order->offering_name ?? 'Unknown' }}
                                    @endif
                                </p>
                                <p class="text-xs text-gray-500 mt-1">{{ $order->created_at->format('M d, Y') }}</p>
                            </div>
                            <div class="flex items-center">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-xl {{ $i <= $order->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                @endfor
                                <span class="ml-2 text-sm text-gray-600">({{ $order->rating }}/5)</span>
                            </div>
                        </div>
                        @if($order->comment)
                            <div class="mt-3 p-3 bg-gray-50 rounded border">
                                <p class="text-sm text-gray-700"><strong>Comment:</strong> {{ $order->comment }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif
    </div>

    <script>
        function toggleReviews() {
            const reviewsSection = document.getElementById('reviewsSection');
            const reviewsPreview = document.getElementById('reviewsPreview');
            const toggleText = document.getElementById('reviewsToggleText');
            
            if (reviewsSection.classList.contains('hidden')) {
                reviewsSection.classList.remove('hidden');
                reviewsPreview.classList.add('hidden');
                toggleText.textContent = 'Hide Reviews';
            } else {
                reviewsSection.classList.add('hidden');
                reviewsPreview.classList.remove('hidden');
                toggleText.textContent = 'Show All Reviews';
            }
        }
    </script>
@endsection
