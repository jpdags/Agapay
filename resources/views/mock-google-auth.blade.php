<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google OAuth - Testing</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-md p-8 max-w-md w-full">
            <!-- Google-style Header -->
            <div class="flex items-center justify-center mb-6">
                <div class="w-12 h-12 bg-red-500 rounded-full flex items-center justify-center mr-3">
                    <span class="text-white font-bold text-xl">G</span>
                </div>
                <h2 class="text-2xl font-semibold text-gray-800">Google</h2>
            </div>

            <div class="text-center mb-6">
                <h3 class="text-xl font-medium text-gray-700">Choose an account</h3>
                <p class="text-gray-600 mt-2">to continue to Your App</p>
            </div>

            <!-- Test Account Options -->
            <div class="space-y-4 mb-6">
                <form method="POST" action="{{ route('google.callback') }}" class="w-full">
                    @csrf
                    <button type="submit" 
                            class="w-full flex items-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition duration-150 cursor-pointer">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center mr-4">
                            <span class="text-white font-semibold">T</span>
                        </div>
                        <div class="text-left">
                            <p class="font-medium text-gray-900">Test User</p>
                            <p class="text-gray-600 text-sm">test@example.com</p>
                        </div>
                    </button>
                </form>

                <div class="flex items-center p-4 border border-gray-300 rounded-lg opacity-50">
                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-4">
                        <span class="text-white font-semibold">A</span>
                    </div>
                    <div class="text-left">
                        <p class="font-medium text-gray-900">Another User</p>
                        <p class="text-gray-600 text-sm">user@example.com</p>
                    </div>
                </div>
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800 text-center">
                    <strong>Testing Only:</strong> This is a mock Google OAuth screen for development purposes.
                </p>
            </div>

            <!-- Cancel Button -->
            <div class="text-center">
                <a href="{{ route('login') }}" 
                   class="text-gray-600 hover:text-gray-800 text-sm font-medium">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</body>
</html>