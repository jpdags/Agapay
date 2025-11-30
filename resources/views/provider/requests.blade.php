@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">Incoming Requests</h1>

<div class="space-y-4">
    @foreach($requests as $req)
        <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">

            <h2 class="text-lg font-semibold">{{ $req->customer }}</h2>
            <p class="text-gray-700">{{ $req->service }}</p>
            <p class="text-sm text-gray-600 mt-1">{{ $req->details }}</p>

            <p class="text-xs text-gray-500 mt-2">📅 {{ $req->schedule }}</p>

            <div class="mt-4 flex gap-2">
                <button class="bg-green-600 text-white px-4 py-2 rounded">Accept</button>
                <button class="bg-red-600 text-white px-4 py-2 rounded">Decline</button>
                <button class="bg-blue-600 text-white px-4 py-2 rounded">Message</button>
            </div>
        </div>
    @endforeach
</div>

@endsection
