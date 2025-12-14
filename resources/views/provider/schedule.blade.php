@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">Upcoming Schedule</h1>

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
                <p class="text-xs text-blue-600 mt-2">💡 <strong>Tip:</strong> Connect your Google Calendar to receive email notifications when customers make bookings!</p>
            </div>
        @else
            <div class="text-center py-8">
                <p class="text-gray-600 mb-4">Connect your Google Calendar to view your appointments here.</p>
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
                        <p class="text-sm text-gray-600 mt-1">Location: {{ $s->customer_address ?? 'Not provided' }}</p>
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
                    @if($s->status === 'accepted')
                        <span class="px-3 py-2 bg-gray-100 text-gray-700 rounded">Awaiting customer completion</span>
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

{{-- Manual Google Calendar sync buttons removed: syncing now happens automatically once connected --}}

@endsection
