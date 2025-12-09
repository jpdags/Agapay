@extends('layouts.customer-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">My Bookings</h1>

<!-- Calendar and Bookings Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- Calendar Section -->
    <div class="bg-white rounded-lg shadow border p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Bookings Calendar</h2>
            <div class="flex gap-2">
                @if(Auth::user()->google_calendar_token)
                    <button onclick="syncToGoogleCalendar()" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Sync to Google
                    </button>
                    <span class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded">✓ Connected</span>
                @else
                    <a href="{{ route('google.calendar.redirect') }}" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                        Connect Google Calendar
                    </a>
                @endif
            </div>
        </div>
        <div id="calendar" class="calendar-container"></div>
    </div>

    <!-- Bookings for Selected Date -->
    <div class="bg-white rounded-lg shadow border p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Bookings</h2>
        <div id="selectedDateBookings" class="space-y-3">
            <p class="text-gray-500 text-center py-4">Select a date to view bookings.</p>
        </div>
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
                            @else
                                Business
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

                @if($booking->status === 'accepted' || $booking->status === 'completed')
                    @if($booking->scheduled_date && Auth::user()->google_calendar_token)
                        <div class="mt-2">
                            <button data-booking-id="{{ $booking->id }}" class="sync-booking-btn px-3 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                                📅 Add to Google Calendar
                            </button>
                        </div>
                    @endif
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

    <style>
        .calendar-container {
            font-family: Arial, sans-serif;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .calendar-nav-btn {
            background: #8B0000;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
        }
        .calendar-nav-btn:hover {
            background: #DC2626;
        }
        .calendar-month-year {
            font-weight: 600;
            font-size: 1.125rem;
        }
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }
        .calendar-weekday {
            text-align: center;
            font-weight: 600;
            font-size: 0.75rem;
            color: #6B7280;
            padding: 0.5rem;
        }
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.25rem;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #E5E7EB;
            border-radius: 0.25rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s;
            color: #1F2937;
            background: #FFFFFF;
            min-height: 40px;
        }
        .calendar-day:hover {
            background: #FEE2E2;
            border-color: #8B0000;
        }
        .calendar-day.other-month {
            color: #D1D5DB;
            background: #F9FAFB;
        }
        .calendar-day.today {
            background: #FEE2E2;
            border-color: #8B0000;
            font-weight: 600;
        }
        .calendar-day.has-booking {
            background: #DC2626;
            color: white;
            font-weight: 600;
        }
        .calendar-day.has-booking:hover {
            background: #8B0000;
        }
        .calendar-day.selected {
            background: #8B0000;
            color: white;
            font-weight: 700;
        }
    </style>

    <!-- Hidden data element for JavaScript -->
    <script type="application/json" id="bookings-data">{!! json_encode($bookings ?? []) !!}</script>
    @if(Auth::user()->google_calendar_token)
    <script type="application/json" id="calendar-events-url">{!! json_encode(route('calendar.events')) !!}</script>
    @endif

    <script>
        // Calendar functionality
        let currentDate = new Date();
        let bookingsData = JSON.parse(document.getElementById('bookings-data').textContent);

        function renderCalendar() {
            const calendarEl = document.getElementById('calendar');
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const daysInMonth = lastDay.getDate();
            const startingDayOfWeek = firstDay.getDay();

            // Month names
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];

            // Build calendar HTML
            let calendarHTML = `
                <div class="calendar-header">
                    <button class="calendar-nav-btn" onclick="previousMonth()">← Prev</button>
                    <div class="calendar-month-year">${monthNames[month]} ${year}</div>
                    <button class="calendar-nav-btn" onclick="nextMonth()">Next →</button>
                </div>
                <div class="calendar-weekdays">
                    <div class="calendar-weekday">Sun</div>
                    <div class="calendar-weekday">Mon</div>
                    <div class="calendar-weekday">Tue</div>
                    <div class="calendar-weekday">Wed</div>
                    <div class="calendar-weekday">Thu</div>
                    <div class="calendar-weekday">Fri</div>
                    <div class="calendar-weekday">Sat</div>
        </div>
                <div class="calendar-days">
            `;

            // Empty cells for days before month starts
            for (let i = 0; i < startingDayOfWeek; i++) {
                calendarHTML += `<div class="calendar-day other-month"></div>`;
            }

            // Days of the month
            const today = new Date();
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const hasBooking = bookingsData.some(b => {
                    // Check if booking scheduled_date matches
                    const bookingDate = b.scheduled_date || b.date || b.created_at;
                    if (!bookingDate) return false;
                    const bookingDateObj = new Date(bookingDate);
                    return bookingDateObj.toISOString().split('T')[0] === dateStr;
                });
                
                const isToday = year === today.getFullYear() && 
                               month === today.getMonth() && 
                               day === today.getDate();

                let dayClass = 'calendar-day';
                if (isToday) dayClass += ' today';
                if (hasBooking) dayClass += ' has-booking';

                calendarHTML += `<div class="${dayClass}" onclick="showBookingsForDate('${dateStr}', this)">${day}</div>`;
            }

            // Empty cells for days after month ends
            const totalCells = startingDayOfWeek + daysInMonth;
            const remainingCells = 42 - totalCells; // 6 rows * 7 days
            for (let i = 0; i < remainingCells && totalCells + i < 42; i++) {
                calendarHTML += `<div class="calendar-day other-month"></div>`;
            }

            calendarHTML += `</div>`;
            calendarEl.innerHTML = calendarHTML;
        }

        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }

        function showBookingsForDate(dateStr, element) {
            // Remove previous selection
            document.querySelectorAll('.calendar-day.selected').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selection to clicked day
            if (element) {
                element.classList.add('selected');
            }

            // Filter bookings for selected date
                    const dayBookings = bookingsData.filter(b => {
                const bookingDate = b.scheduled_date || b.date || b.created_at;
                if (!bookingDate) return false;
                const bookingDateObj = new Date(bookingDate);
                return bookingDateObj.toISOString().split('T')[0] === dateStr;
            });

            // Update bookings section
            const bookingsEl = document.getElementById('selectedDateBookings');
            if (dayBookings.length === 0) {
                bookingsEl.innerHTML = '<p class="text-gray-500 text-center py-4">No bookings for this date.</p>';
            } else {
                bookingsEl.innerHTML = dayBookings.map(b => {
                    // Use the category (offering type) as the headline instead of a generic "Booking"
                    let offeringName = 'Booking';
                    if (b.offering_type === 'product') {
                        offeringName = 'Product';
                    } else if (b.offering_type === 'service') {
                        offeringName = 'Service';
                    } else if (b.offering_type === 'business') {
                        offeringName = 'Business';
                    }
                    const providerName = b.provider?.name || b.provider || 'Provider';
                    const time = b.scheduled_time ? new Date('2000-01-01T' + b.scheduled_time).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' }) : (b.time || 'Time not set');
                    const status = b.status || 'pending';
                    return `
                        <div class="p-4 bg-gray-50 rounded-lg border hover:shadow-md transition">
                            <h4 class="font-semibold text-gray-800">${offeringName}</h4>
                            <p class="text-sm text-gray-700 mt-1">Provider: ${providerName}</p>
                            <p class="text-xs text-gray-500 mt-2">⏰ ${time}</p>
                            <p class="text-xs mt-1">
                                <span class="px-2 py-1 rounded ${status === 'pending' ? 'bg-yellow-100 text-yellow-700' : status === 'accepted' ? 'bg-blue-100 text-blue-700' : status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'}">
                                    ${status.charAt(0).toUpperCase() + status.slice(1)}
                                </span>
                            </p>
    </div>
                    `;
                }).join('');
            }
        }

        // Initialize calendar on page load
        document.addEventListener('DOMContentLoaded', function() {
            renderCalendar();
            
            // Show today's bookings if any
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            // Find and highlight today's date element
            setTimeout(() => {
                const todayElement = document.querySelector('.calendar-day.today');
                if (todayElement) {
                    showBookingsForDate(todayStr, todayElement);
                } else {
                    showBookingsForDate(todayStr, null);
                }
            }, 100);
        });

        // Google Calendar integration
        function syncBookingToCalendar(orderId) {
            fetch(`/calendar/sync/${orderId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ ' + data.message);
                } else {
                    alert('✗ ' + (data.error || 'Failed to sync to Google Calendar'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('✗ Failed to sync to Google Calendar');
            });
        }

        // Handle sync booking buttons
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.sync-booking-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const bookingId = this.getAttribute('data-booking-id');
                    syncBookingToCalendar(bookingId);
                });
            });
        });

        function syncToGoogleCalendar() {
            // Get all accepted/completed bookings with scheduled dates
            const bookingsToSync = bookingsData.filter(b => 
                (b.status === 'accepted' || b.status === 'completed') && b.scheduled_date
            );
            
            if (bookingsToSync.length === 0) {
                alert('No bookings to sync. Make sure you have accepted or completed bookings with scheduled dates.');
                return;
            }

            let synced = 0;
            let failed = 0;

            bookingsToSync.forEach(booking => {
                syncBookingToCalendar(booking.id);
            });
        }

        // Load Google Calendar events on page load
        (function() {
            const eventsUrlElement = document.getElementById('calendar-events-url');
            if (eventsUrlElement) {
                const eventsUrl = JSON.parse(eventsUrlElement.textContent);
                fetch(eventsUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.events && data.events.length > 0) {
                            // Merge Google Calendar events with bookings
                            // You can enhance this to show Google Calendar events on the calendar
                            console.log('Google Calendar events loaded:', data.events);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading Google Calendar events:', error);
                    });
            }
        })();
    </script>
@endsection
