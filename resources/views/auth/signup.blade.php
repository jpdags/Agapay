<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
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
        .auth-bg {
            background: linear-gradient(135deg, #8B0000 0%, #DC2626 100%);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Left Side - Form -->
        <div class="flex-1 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24">
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div>
                    <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                        Create an account
                    </h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Already have an account? 
                        <a href="/login" class="font-medium text-dark-red hover:text-light-red">
                            Sign in
                        </a>
                    </p>
                </div>

                <div class="mt-8">
                    @if ($errors->any())
                        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form class="space-y-6" action="{{ route('signup.submit') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="firstName" class="block text-sm font-medium text-gray-700">
                                    First Name
                                </label>
                                <div class="mt-1">
                                    <input id="firstName" name="firstName" type="text" autocomplete="given-name" required 
                                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-dark-red focus:border-dark-red sm:text-sm"
                                        placeholder="First name">
                                </div>
                            </div>
                            <div>
                                <label for="lastName" class="block text-sm font-medium text-gray-700">
                                    Last Name
                                </label>
                                <div class="mt-1">
                                    <input id="lastName" name="lastName" type="text" autocomplete="family-name" required 
                                        class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-dark-red focus:border-dark-red sm:text-sm"
                                        placeholder="Last name">
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email address
                            </label>
                            <div class="mt-1">
                                <input id="email" name="email" type="email" autocomplete="email" required 
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-dark-red focus:border-dark-red sm:text-sm"
                                    placeholder="Enter your email">
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <div class="mt-1">
                                <input id="password" name="password" type="password" autocomplete="current-password" required 
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-dark-red focus:border-dark-red sm:text-sm"
                                    placeholder="Enter your password">
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                Confirm Password
                            </label>
                            <div class="mt-1">
                                <input id="password_confirmation" name="password_confirmation" type="password" required 
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-dark-red focus:border-dark-red sm:text-sm"
                                    placeholder="Confirm your password">
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3 mb-4">
                            <p class="text-sm text-yellow-800">
                                <strong>⚠️ Account Verification:</strong> While phone and address are optional during registration, we recommend adding them now to verify your account. You can add them later in your profile settings, but your account will remain unverified until these details are provided.
                            </p>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700">
                                Phone Number <span class="text-yellow-600 text-xs font-semibold">(Recommended)</span>
                            </label>
                            <div class="mt-1">
                                <input id="phone" name="phone" type="text" autocomplete="tel" 
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-dark-red focus:border-dark-red sm:text-sm"
                                    placeholder="e.g., 09123456789">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Used for account verification and contact purposes</p>
                        </div>

                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700">
                                Address <span class="text-yellow-600 text-xs font-semibold">(Recommended)</span>
                            </label>
                            <div class="mt-1">
                                <input id="address" name="address" type="text" autocomplete="street-address" 
                                    class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-dark-red focus:border-dark-red sm:text-sm"
                                    placeholder="e.g., 123 Main St, Barangay Name">
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Used for account verification and service delivery</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                I want to register as:
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-dark-red transition duration-150">
                                    <input type="radio" name="user_type" value="customer" required
                                        class="h-4 w-4 text-dark-red focus:ring-dark-red border-gray-300">
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">Customer</span>
                                        <span class="block text-xs text-gray-500">Browse and book services</span>
                                    </div>
                                </label>
                                <label class="flex items-center p-4 border-2 border-gray-300 rounded-lg cursor-pointer hover:border-dark-red transition duration-150">
                                    <input type="radio" name="user_type" value="provider" required
                                        class="h-4 w-4 text-dark-red focus:ring-dark-red border-gray-300">
                                    <div class="ml-3">
                                        <span class="block text-sm font-medium text-gray-900">Service Provider</span>
                                        <span class="block text-xs text-gray-500">Offer your services to customers</span>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <button type="submit"
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-dark-red hover:bg-light-red focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-dark-red transition duration-150">
                                Sign Up
                            </button>
                        </div>
                    </form>

                    <div class="mt-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">
                                    Or continue with
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Google Sign-Up Button -->
                    <div class="mt-6 grid grid-cols-1 gap-3">
                        <a href="{{ route('google.redirect') }}" 
                        class="w-full inline-flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 transition duration-150">
                            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            Continue with Google
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
