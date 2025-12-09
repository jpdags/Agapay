@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">Upcoming Schedule</h1>

<!-- Calendar and Schedule Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- Calendar Section -->
    <div class="bg-white rounded-lg shadow border p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-800">Schedule Calendar</h2>
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

    <!-- Appointments for Selected Date -->
    <div class="bg-white rounded-lg shadow border p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Appointments</h2>
        <div id="selectedDateAppointments" class="space-y-3">
            <p class="text-gray-500 text-center py-4">Select a date to view appointments.</p>
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

<!-- All Scheduled Appointments -->
<div class="mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">All Scheduled Appointments</h2>
<div class="space-y-4">
        @forelse($schedule as $s)
        <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">{{ $s->customer ?? 'Customer' }}</h2>
                        <p class="text-gray-700">{{ $s->service ?? 'Service' }}</p>
                        <p class="text-xs text-gray-500 mt-1">📅 {{ $s->date ?? 'Not set' }} • ⏰ {{ $s->time ?? 'Not set' }}</p>
                    </div>
                    <span class="px-3 py-1 text-sm rounded-full 
                        @if($s->status === 'pending') bg-yellow-100 text-yellow-700
                        @elseif($s->status === 'accepted') bg-blue-100 text-blue-700
                        @elseif($s->status === 'completed') bg-green-100 text-green-700
                        @elseif($s->status === 'declined') bg-red-100 text-red-700
                        @else bg-gray-100 text-gray-700 @endif">
                        {{ ucfirst($s->status ?? 'Unknown') }}
                    </span>
                </div>
            <div class="mt-4 flex gap-2">
                    @if($s->status !== 'completed')
                    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">Mark Completed</button>
                    @endif
                    @if($s->date && Auth::user()->google_calendar_token)
                        <button onclick="syncAppointmentToCalendar({{ $s->id }})" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            📅 Add to Google Calendar
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white p-5 rounded shadow border">
                <p class="text-gray-500 text-center">No scheduled appointments.</p>
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
    .calendar-day.has-appointment {
        background: #DC2626;
        color: white;
        font-weight: 600;
    }
    .calendar-day.has-appointment:hover {
        background: #8B0000;
    }
    .calendar-day.selected {
        background: #8B0000;
        color: white;
        font-weight: 700;
    }
</style>

<!-- Hidden data element for JavaScript -->
<script type="application/json" id="schedule-data">{!! json_encode($schedule ?? []) !!}</script>
<script type="application/json" id="google-calendar-connected">{!! json_encode(Auth::user()->google_calendar_token ? true : false) !!}</script>

<script>
    // Calendar functionality
    let currentDate = new Date();
    let scheduleData = JSON.parse(document.getElementById('schedule-data').textContent);

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
            const hasAppointment = scheduleData.some(s => {
                // Check if schedule date matches
                const scheduleDate = s.date || s.scheduled_date || s.created_at;
                if (!scheduleDate) return false;
                const scheduleDateObj = new Date(scheduleDate);
                return scheduleDateObj.toISOString().split('T')[0] === dateStr;
            });
            
            const isToday = year === today.getFullYear() && 
                           month === today.getMonth() && 
                           day === today.getDate();

            let dayClass = 'calendar-day';
            if (isToday) dayClass += ' today';
            if (hasAppointment) dayClass += ' has-appointment';

            calendarHTML += `<div class="${dayClass}" onclick="showAppointmentsForDate('${dateStr}', this)">${day}</div>`;
        }

        // Empty cells for days after month ends
        const totalCells = startingDayOfWeek + daysInMonth;
        const remainingCells = 42 - totalCells;
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

    function showAppointmentsForDate(dateStr, element) {
        // Remove previous selection
        document.querySelectorAll('.calendar-day.selected').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Add selection to clicked day
        if (element) {
            element.classList.add('selected');
        }

        // Filter appointments for selected date
        const dayAppointments = scheduleData.filter(s => {
            const scheduleDate = s.date || s.scheduled_date || s.created_at;
            if (!scheduleDate) return false;
            const scheduleDateObj = new Date(scheduleDate);
            return scheduleDateObj.toISOString().split('T')[0] === dateStr;
        });

        // Update appointments section
        const appointmentsEl = document.getElementById('selectedDateAppointments');
        if (dayAppointments.length === 0) {
            appointmentsEl.innerHTML = '<p class="text-gray-500 text-center py-4">No appointments scheduled for this date.</p>';
        } else {
            const isGoogleConnected = JSON.parse(document.getElementById('google-calendar-connected').textContent);
            appointmentsEl.innerHTML = dayAppointments.map(s => {
                const status = s.status || 'unknown';
                const statusClass = status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                                   status === 'accepted' ? 'bg-blue-100 text-blue-700' :
                                   status === 'completed' ? 'bg-green-100 text-green-700' :
                                   status === 'declined' ? 'bg-red-100 text-red-700' :
                                   'bg-gray-100 text-gray-700';
                const statusText = status.charAt(0).toUpperCase() + status.slice(1);
                
                const markCompletedButton = status !== 'completed' ? `
                    <button class="mt-3 bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                        Mark Completed
                    </button>
                ` : '';
                
                const syncButton = isGoogleConnected ? `
                    <button onclick="syncAppointmentToCalendar(${s.id})" class="mt-2 bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                        📅 Add to Google Calendar
                    </button>
                ` : '';
                
                return `
                    <div class="p-4 bg-gray-50 rounded-lg border hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-semibold text-gray-800">${s.customer || 'Customer'}</h4>
                                <h2 class="text-lg font-semibold text-gray-800 mt-1">${s.service || 'Service'}</h2>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full ${statusClass}">
                                ${statusText}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">⏰ ${s.time || 'Time not set'}</p>
                        ${markCompletedButton}
                        ${syncButton}
                </div>
                `;
            }).join('');
        }
    }

    // Initialize calendar on page load
    document.addEventListener('DOMContentLoaded', function() {
        renderCalendar();
        
        // Show today's appointments if any
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        // Find and highlight today's date element
        setTimeout(() => {
            const todayElement = document.querySelector('.calendar-day.today');
            if (todayElement) {
                showAppointmentsForDate(todayStr, todayElement);
            } else {
                showAppointmentsForDate(todayStr, null);
            }
        }, 100);
    });

    // Google Calendar integration
    function syncAppointmentToCalendar(orderId) {
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

    function syncToGoogleCalendar() {
        // Get all appointments with scheduled dates
        const appointmentsToSync = scheduleData.filter(s => s.date || s.scheduled_date);
        
        if (appointmentsToSync.length === 0) {
            alert('No appointments to sync. Make sure you have appointments with scheduled dates.');
            return;
        }

        let synced = 0;
        let failed = 0;

        appointmentsToSync.forEach(appointment => {
            if (appointment.id) {
                syncAppointmentToCalendar(appointment.id);
                synced++;
            } else {
                failed++;
            }
        });

        if (failed > 0) {
            alert(`Synced ${synced} appointments. ${failed} appointments could not be synced (missing order ID).`);
        } else {
            alert(`Syncing ${synced} appointments to Google Calendar...`);
        }
    }
</script>

@endsection
