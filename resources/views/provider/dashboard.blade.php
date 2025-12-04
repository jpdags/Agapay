@extends('layouts.provider-main')

@section('content')
    <div style="background-color: transparent;">
        <h2 class="text-xl font-semibold mb-4 text-gray-800">Welcome to your Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 shadow rounded-lg" style="background-color: #FFFFFF;">
                <p class="text-sm text-gray-600 mb-2">Completed Jobs</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['completed_jobs'] ?? 0 }}</p>
            </div>

            <div class="bg-white p-6 shadow rounded-lg" style="background-color: #FFFFFF;">
                <p class="text-sm text-gray-600 mb-2">Pending Requests</p>
                <p class="text-2xl font-bold text-orange-500">{{ $stats['pending_requests'] ?? 0 }}</p>
            </div>

            <div class="bg-white p-6 shadow rounded-lg" style="background-color: #FFFFFF;">
                <p class="text-sm text-gray-600 mb-2">Total Earnings</p>
                <p class="text-2xl font-bold text-green-600">₱{{ number_format($stats['earnings'] ?? 0, 2) }}</p>
            </div>
        </div>

        <h3 class="text-xl font-semibold mb-4 text-gray-800">Recent Requests</h3>
        @forelse($requests as $request)
            <div class="bg-white p-6 rounded-lg shadow mb-4" style="background-color: #FFFFFF;">
                <p class="text-gray-800"><strong>{{ $request->customer_name ?? 'Customer' }}</strong> has requested a service: {{ $request->service ?? 'Service' }}.</p>
                <p class="text-sm text-gray-600 mt-1"><small>Scheduled for {{ $request->schedule ?? 'Not scheduled' }}</small></p>
            </div>
        @empty
            <div class="bg-white p-6 rounded-lg shadow mb-4" style="background-color: #FFFFFF;">
                <p class="text-gray-500 text-center">No recent requests.</p>
            </div>
        @endforelse
    </div>
@endsection
