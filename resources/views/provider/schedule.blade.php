@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">Upcoming Schedule</h1>

<!-- Calendar and Schedule Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- Calendar Section -->
    <div class="bg-white rounded-lg shadow border p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Schedule Calendar</h2>
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

<!-- All Scheduled Appointments -->
<div class="mb-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">All Scheduled Appointments</h2>
    <div class="space-y-4">
        @forelse($schedule as $s)
            <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $s->customer ?? 'Customer' }}</h2>
                        <p class="text-gray-700">{{ $s->service ?? 'Service' }}</p>
                        <p class="text-xs text-gray-500 mt-1">📅 {{ $s->date ?? 'Not set' }} • ⏰ {{ $s->time ?? 'Not set' }}</p>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">Mark Completed</button>
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
            appointmentsEl.innerHTML = dayAppointments.map(s => `
                <div class="p-4 bg-gray-50 rounded-lg border hover:shadow-md transition">
                    <h4 class="font-semibold text-gray-800">${s.customer || 'Customer'}</h4>
                    <p class="text-sm text-gray-700 mt-1">${s.service || 'Service'}</p>
                    <p class="text-xs text-gray-500 mt-2">⏰ ${s.time || 'Time not set'}</p>
                    <button class="mt-3 bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                        Mark Completed
                    </button>
                </div>
            `).join('');
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
</script>

@endsection
