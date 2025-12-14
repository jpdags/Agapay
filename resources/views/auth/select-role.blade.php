<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Role</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .bg-dark-red {
            background-color: #8B0000;
        }
        .bg-light-red {
            background-color: #DC2626;
        }
        .text-dark-red {
            color: #8B0000;
        }
        .border-dark-red {
            border-color: #8B0000;
        }
        .hover-border-dark-red:hover {
            border-color: #8B0000;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Choose Your Role
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Select how you want to use the platform
                </p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('google.role.submit') }}" method="POST" class="mt-8 space-y-6">
                @csrf
                <div class="space-y-4">
                    <label class="block relative">
                        <input type="radio" name="user_type" value="customer" required
                            class="peer sr-only">
                        <div class="p-6 border-2 border-gray-300 rounded-lg cursor-pointer transition-all duration-200 peer-checked:border-dark-red peer-checked:bg-red-50 hover-border-dark-red">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Customer</h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Browse and book services from local providers
                                    </p>
                                    <ul class="mt-2 space-y-1">
                                        <li class="text-xs text-gray-500 flex items-center">
                                            <svg class="h-4 w-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Access to all services
                                        </li>
                                        <li class="text-xs text-gray-500 flex items-center">
                                            <svg class="h-4 w-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Easy booking management
                                        </li>
                                        <li class="text-xs text-gray-500 flex items-center">
                                            <svg class="h-4 w-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Track your orders
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </label>

                    <label class="block relative">
                        <input type="radio" name="user_type" value="provider" required
                            class="peer sr-only">
                        <div class="p-6 border-2 border-gray-300 rounded-lg cursor-pointer transition-all duration-200 peer-checked:border-dark-red peer-checked:bg-red-50 hover-border-dark-red">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-lg font-semibold text-gray-900">Service Provider</h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Offer your services and manage bookings
                                    </p>
                                    <ul class="mt-2 space-y-1">
                                        <li class="text-xs text-gray-500 flex items-center">
                                            <svg class="h-4 w-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            List your services
                                        </li>
                                        <li class="text-xs text-gray-500 flex items-center">
                                            <svg class="h-4 w-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Manage bookings & requests
                                        </li>
                                        <li class="text-xs text-gray-500 flex items-center">
                                            <svg class="h-4 w-4 mr-1 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Grow your business
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </label>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-dark-red hover:bg-light-red focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-dark-red transition duration-150">
                        Continue
                    </button>
                </div>
            </form>

            <div class="text-center">
                <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-dark-red">
                    ← Back to login
                </a>
            </div>
        </div>
    </div>
</body>
</html>

