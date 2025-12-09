@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">Incoming Requests</h1>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

<div class="space-y-4">
    @forelse($requests as $order)
        <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">
            <div class="flex justify-between items-start mb-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">{{ $order->customer->name ?? 'Customer' }}</h2>
                    <p class="text-sm text-gray-600">Order #{{ $order->id }}</p>
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

            <div class="mb-3">
                <p class="text-gray-800 font-medium">
                    @if($order->offering_type === 'product')
                        Product: {{ $order->offering->name ?? 'Unknown' }}
                    @elseif($order->offering_type === 'service')
                        Service: {{ $order->offering->name ?? 'Unknown' }}
                    @else
                        Business: {{ $order->offering->name ?? 'Unknown' }}
                    @endif
                </p>
                @if($order->offering_type === 'product' && $order->quantity > 1)
                    <p class="text-sm text-gray-600">Quantity: {{ $order->quantity }}</p>
                @endif
                @if($order->notes)
                    <p class="text-sm text-gray-600 mt-1">Notes: {{ $order->notes }}</p>
                @endif
                @if($order->total_amount > 0)
                    <p class="text-sm font-medium text-green-600 mt-1">Amount: ₱{{ number_format($order->total_amount, 2) }}</p>
                @endif
            </div>

            @if($order->scheduled_date)
                <p class="text-xs text-gray-500 mb-3">
                    📅 {{ $order->scheduled_date->format('M d, Y') }}
                    @if($order->scheduled_time)
                        • ⏰ {{ \Carbon\Carbon::parse($order->scheduled_time)->format('g:i A') }}
                    @endif
                </p>
            @endif

            <div class="mt-4 flex gap-2">
                @if($order->status === 'pending')
                    <form action="{{ route('provider.order.update-status', $order->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="accepted">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">Accept</button>
                    </form>
                    <form action="{{ route('provider.order.update-status', $order->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="declined">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">Decline</button>
                    </form>
                @elseif($order->status === 'accepted')
                    <form action="{{ route('provider.order.update-status', $order->id) }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Mark as Completed</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="bg-white p-5 rounded shadow border">
            <p class="text-gray-500 text-center">No incoming requests.</p>
        </div>
    @endforelse
</div>

@endsection
