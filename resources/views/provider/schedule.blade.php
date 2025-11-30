@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">Upcoming Schedule</h1>

<div class="space-y-4">
    @foreach($schedule as $s)
        <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">

            <h2 class="text-lg font-semibold">{{ $s->customer }}</h2>
            <p class="text-gray-700">{{ $s->service }}</p>

            <p class="text-xs text-gray-500 mt-1">📅 {{ $s->date }} • ⏰ {{ $s->time }}</p>

            <div class="mt-4">
                <button class="bg-green-600 text-white px-4 py-2 rounded">Mark Completed</button>
            </div>
        </div>
    @endforeach
</div>

@endsection
