@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">My Services</h1>

<div class="mb-4">
    <button class="bg-dark-red text-white px-4 py-2 rounded">Add New Service</button>
</div>

<div class="space-y-4">
    @foreach($services as $s)
        <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">

            <h2 class="text-lg font-semibold">{{ $s->name }}</h2>
            <p class="text-sm text-gray-700">{{ $s->description }}</p>
            <p class="text-green-600 font-medium mt-1">{{ $s->price }}</p>

            <div class="mt-4 flex gap-2">
                <button class="bg-blue-500 text-white px-4 py-2 rounded">Edit</button>
                <button class="bg-red-500 text-white px-4 py-2 rounded">Remove</button>
            </div>
        </div>
    @endforeach
</div>

@endsection
