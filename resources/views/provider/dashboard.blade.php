@extends('layouts.provider-main')

@section('content')
    <h2 class="text-xl font-semibold mb-4">Welcome to your Dashboard</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 shadow rounded">
            <p class="text-sm text-gray-600">Completed Jobs</p>
            <p class="text-2xl font-bold">{{ $stats['completed_jobs'] }}</p>
        </div>

        <div class="bg-white p-6 shadow rounded">
            <p class="text-sm text-gray-600">Pending Requests</p>
            <p class="text-2xl font-bold text-orange-500">{{ $stats['pending_requests'] }}</p>
        </div>

        <div class="bg-white p-6 shadow rounded">
            <p class="text-sm text-gray-600">Total Earnings</p>
            <p class="text-2xl font-bold text-green-600">₱{{ number_format($stats['earnings'], 2) }}</p>
        </div>
    </div>

    <h3 class="text-xl font-semibold mb-4">Recent Requests</h3>
    @forelse($requests as $request)
        <div class="bg-white p-6 rounded shadow mb-4">
            <p><strong>{{ $request->customer_name ?? 'Customer' }}</strong> has requested a service: {{ $request->service ?? 'Service' }}.</p>
            <p><small>Scheduled for {{ $request->schedule ?? 'Not scheduled' }}</small></p>
        </div>
    @empty
        <div class="bg-white p-6 rounded shadow mb-4">
            <p class="text-gray-500 text-center">No recent requests.</p>
        </div>
    @endforelse
@endsection
