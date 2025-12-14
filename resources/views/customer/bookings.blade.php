@extends('layouts.customer-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">My Bookings</h1>

<!-- Google Calendar Section -->
<div class="mb-8">
    <div class="bg-white rounded-lg shadow border p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">My Google Calendar</h2>
            <div class="flex gap-2">
                @if(Auth::user()->google_calendar_token)
                    <span class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded">✓ Connected</span>
                    <a href="{{ route('google.calendar.redirect') }}" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition" title="Reconnect to ensure email notifications work">
                        Reconnect
                    </a>
                    <form action="{{ route('google.calendar.disconnect') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700 transition" onclick="return confirm('Are you sure you want to disconnect? You will stop receiving email notifications.')">
                            Disconnect
                        </button>
                    </form>
                @else
                    <a href="{{ route('google.calendar.redirect') }}" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Connect Google Calendar
                    </a>
                @endif
            </div>
        </div>
        
        @if(isset($calendarEmbedUrl) && $calendarEmbedUrl)
            <div class="w-full" style="height: 600px;">
                <iframe 
                    src="{{ $calendarEmbedUrl }}" 
                    style="border: 0; width: 100%; height: 100%;" 
                    frameborder="0" 
                    scrolling="no">
                </iframe>
            </div>
        @elseif(Auth::user()->google_calendar_token)
            <div class="text-center py-8">
                <p class="text-gray-600 mb-4">Loading your Google Calendar...</p>
                <p class="text-sm text-gray-500 mb-2">If the calendar doesn't appear, make sure your Google Calendar is set to be publicly accessible or try refreshing the page.</p>
                <p class="text-xs text-blue-600 mt-2">💡 <strong>Tip:</strong> Connect your Google Calendar to receive email notifications about your bookings!</p>
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-600 mb-4">Connect your Google Calendar to view your bookings here.</p>
                <a href="{{ route('google.calendar.redirect') }}" class="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    Connect Google Calendar
                </a>
            </div>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if(session('warning'))
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        <p class="font-semibold">⚠️ Important:</p>
        <p>{{ session('warning') }}</p>
    </div>
@endif

<!-- All Bookings -->
<div class="mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">All Bookings</h2>
        <div class="space-y-4">
        @forelse($bookings as $booking)
            <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">
                <div class="flex justify-between items-start mb-3">
                        <div>
                        <h2 class="text-lg font-semibold text-gray-800">
                            @if($booking->offering_type === 'product')
                                Product
                            @elseif($booking->offering_type === 'service')
                                Service
                            @endif
                        </h2>
                        <p class="text-gray-700">Provider: {{ $booking->provider->name ?? 'Unknown' }}</p>
                        <p class="text-xs text-gray-500 mt-1">Order #{{ $booking->id }}</p>
                        </div>
                        <span class="px-3 py-1 text-sm rounded-full 
                        @if($booking->status === 'pending') bg-yellow-100 text-yellow-700
                        @elseif($booking->status === 'accepted') bg-blue-100 text-blue-700
                        @elseif($booking->status === 'completed') bg-green-100 text-green-700
                        @elseif($booking->status === 'declined') bg-red-100 text-red-700
                        @else bg-gray-100 text-gray-700 @endif">
                        {{ ucfirst($booking->status) }}
                        </span>
                    </div>

                @if($booking->offering_type === 'product' && $booking->quantity > 1)
                    <p class="text-sm text-gray-600 mb-2">Quantity: {{ $booking->quantity }}</p>
                @endif
                
                @if($booking->total_amount > 0)
                    <p class="text-sm font-medium text-green-600 mb-2">Amount: ₱{{ number_format($booking->total_amount, 2) }}</p>
                        @endif

                @if($booking->notes)
                    <p class="text-sm text-gray-600 mb-2">Notes: {{ $booking->notes }}</p>
                        @endif

                @if($booking->scheduled_date)
                    <p class="text-xs text-gray-500 mb-2">
                        📅 {{ $booking->scheduled_date->format('M d, Y') }}
                        @if($booking->scheduled_time)
                            • ⏰ {{ \Carbon\Carbon::parse($booking->scheduled_time)->format('g:i A') }}
                        @endif
                    </p>
                @endif
                
                <p class="text-xs text-gray-400">Booked on: {{ $booking->created_at->format('M d, Y g:i A') }}</p>

                @if($booking->status === 'accepted')
                    <form action="{{ route('order.complete', $booking->id) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition text-sm">
                            Mark as Completed
                        </button>
                    </form>
                @endif

                @if($booking->status === 'completed')
                    @if($booking->rating)
                        <div class="mt-4 p-3 bg-gray-50 rounded">
                            <p class="text-sm font-semibold text-gray-800 mb-1">Your Rating:</p>
                            <div class="flex items-center mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="text-2xl {{ $i <= $booking->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                                @endfor
                                <span class="ml-2 text-sm text-gray-600">({{ $booking->rating }}/5)</span>
                            </div>
                            @if($booking->comment)
                                <p class="text-sm text-gray-700"><strong>Comment:</strong> {{ $booking->comment }}</p>
                            @endif
                        </div>
                    @else
                        <div class="mt-4 p-4 bg-blue-50 rounded border border-blue-200">
                            <p class="text-sm font-semibold text-blue-800 mb-3">Rate this service:</p>
                            <form action="{{ route('order.rate', $booking->id) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="block text-sm text-gray-700 mb-2">Rating (1-5 stars):</label>
                                    <div class="flex items-center space-x-2" id="rating-stars-{{ $booking->id }}">
                                        @for($i = 1; $i <= 5; $i++)
                                            <label class="cursor-pointer">
                                                <input type="radio" name="rating" value="{{ $i }}" required class="hidden star-rating" data-booking-id="{{ $booking->id }}">
                                                <span class="text-3xl star-icon text-gray-300 hover:text-yellow-300 transition" data-rating="{{ $i }}">★</span>
                                            </label>
                                        @endfor
                                    </div>
                                    <input type="hidden" name="selected_rating" id="selected_rating_{{ $booking->id }}" value="">
                                </div>
                                <script>
                                    (function() {
                                        const bookingId = {{ $booking->id }};
                                        const stars = document.querySelectorAll('#rating-stars-' + bookingId + ' .star-icon');
                                        const radios = document.querySelectorAll('#rating-stars-' + bookingId + ' input[type="radio"]');
                                        
                                        stars.forEach((star, index) => {
                                            star.addEventListener('click', function() {
                                                const rating = parseInt(this.getAttribute('data-rating'));
                                                document.getElementById('selected_rating_' + bookingId).value = rating;
                                                
                                                // Update star colors
                                                stars.forEach((s, i) => {
                                                    if (i < rating) {
                                                        s.classList.remove('text-gray-300');
                                                        s.classList.add('text-yellow-400');
                                                    } else {
                                                        s.classList.remove('text-yellow-400');
                                                        s.classList.add('text-gray-300');
                                                    }
                                                });
                                                
                                                // Check the corresponding radio button
                                                radios[index].checked = true;
                                            });
                                            
                                            star.addEventListener('mouseenter', function() {
                                                const rating = parseInt(this.getAttribute('data-rating'));
                                                stars.forEach((s, i) => {
                                                    if (i < rating) {
                                                        s.classList.add('text-yellow-300');
                                                    }
                                                });
                                            });
                                            
                                            star.addEventListener('mouseleave', function() {
                                                const selectedRating = parseInt(document.getElementById('selected_rating_' + bookingId).value || '0');
                                                stars.forEach((s, i) => {
                                                    s.classList.remove('text-yellow-300');
                                                    if (i < selectedRating) {
                                                        s.classList.add('text-yellow-400');
                                                    }
                                                });
                                            });
                                        });
                                    })();
                                </script>
                                <div class="mb-3">
                                    <label for="comment_{{ $booking->id }}" class="block text-sm text-gray-700 mb-1">Comment (optional):</label>
                                    <textarea id="comment_{{ $booking->id }}" name="comment" rows="3" 
                                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red"
                                        placeholder="Share your experience..."></textarea>
                                </div>
                                <button type="submit" class="px-4 py-2 bg-dark-red text-white rounded hover:bg-red-800 transition text-sm">
                                    Submit Rating
                                </button>
                            </form>
                        </div>
                    @endif
                @endif
            </div>
        @empty
                <div class="bg-white p-5 rounded shadow border">
                    <p class="text-gray-500 text-center">No bookings yet.</p>
                </div>
            @endforelse
    </div>
                    </div>

    {{-- Manual Google Calendar sync buttons removed: syncing now happens automatically once connected --}}
@endsection
