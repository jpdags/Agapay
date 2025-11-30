@extends('layouts.customer-main')

@section('content')
    <div class="max-w-4xl mx-auto p-6">

        <h1 class="text-2xl font-bold text-dark-red mb-4">Settings</h1>
        <p class="text-gray-600 mb-6">Manage your account and personal details</p>

        <!-- Profile Section -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h2>

            <div class="flex items-center gap-4 mb-4">
                <img class="w-16 h-16 rounded-full object-cover"
                    src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=DC2626&color=fff">
                <button class="px-4 py-2 bg-dark-red text-white rounded hover:bg-red-800">
                    Change Photo
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="text-sm text-gray-600">Full Name</label>
                    <input type="text" value="{{ $user->name }}"
                        class="mt-1 w-full px-3 py-2 border rounded-lg">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Email</label>
                    <input type="email" value="{{ $user->email }}"
                        class="mt-1 w-full px-3 py-2 border rounded-lg">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Phone Number</label>
                    <input type="text" value="{{ $user->phone }}"
                        class="mt-1 w-full px-3 py-2 border rounded-lg">
                </div>

                <div>
                    <label class="text-sm text-gray-600">Address</label>
                    <input type="text" value="{{ $user->address }}"
                        class="mt-1 w-full px-3 py-2 border rounded-lg">
                </div>
            </div>

            <button class="mt-4 px-5 py-2 bg-dark-red text-white rounded hover:bg-red-800">
                Save Changes
            </button>
        </div>

        <!-- Barangay Verification -->
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Barangay Information</h2>

            <p class="text-gray-700"><strong>Barangay:</strong> {{ $user->barangay }}</p>
            <p class="text-gray-700 mt-1">
                <strong>Status:</strong>
                @if($user->is_verified)
                    <span class="px-2 py-1 bg-green-100 text-green-700 text-sm rounded-full">Verified</span>
                @else
                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-sm rounded-full">Pending Verification</span>
                @endif
            </p>

            <button class="mt-4 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                View Verification Details
            </button>
        </div>

        <!-- Account Settings -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Account Settings</h2>

            <div class="space-y-4">

                <button class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Change Password
                </button>

                <button class="w-full px-4 py-2 bg-gray-200 text-gray-800 rounded hover:bg-gray-300">
                    Manage Notifications
                </button>

                <button class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Delete Account
                </button>
            </div>
        </div>

    </div>
@endsection
