@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">Verification Status</h1>

<div class="bg-white p-6 rounded shadow border">

    <h2 class="text-xl font-semibold">{{ $provider->name }}</h2>

    <p class="mt-2">
        <strong>Status:</strong>
        <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-sm">
            {{ $provider->status }}
        </span>
    </p>

    <p class="text-gray-700 mt-1"><strong>Barangay:</strong> {{ $provider->barangay }}</p>
    <p class="text-gray-500 text-sm mt-1">Verified at: {{ $provider->verified_at }}</p>

    <h3 class="text-lg font-semibold mt-4">Requirements</h3>
    <ul class="list-disc ml-5 text-gray-700">
        @foreach($provider->requirements as $req)
            <li>{{ $req }}</li>
        @endforeach
    </ul>

    <button class="mt-4 bg-dark-red text-white px-4 py-2 rounded">
        Upload Additional Documents
    </button>

</div>

@endsection
