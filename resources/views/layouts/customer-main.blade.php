<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agapay - Customer Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    
    <style>
        .bg-dark-red { background-color: #8B0000; }
        .bg-light-red { background-color: #DC2626; }
        .text-dark-red { color: #8B0000; }
        .border-dark-red { border-color: #8B0000; }

        .sidebar-link { transition: all 0.25s ease; }
        .sidebar-link:hover { background-color: #FEE2E2; transform: translateX(4px); }

        .sidebar-active {
            background-color: #FEE2E2;
            border-right: 4px solid #8B0000;
            color: #8B0000 !important;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Sidebar -->
    <div class="sidebar w-64 bg-white shadow-lg flex flex-col">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-dark-red">Agapay</h1>
            <p class="text-sm text-gray-600">Suki sa Barangay</p>
        </div>

        <nav class="mt-6 flex flex-col h-full">
            <div class="flex-1">
                <!-- Home Link -->
                <a href="{{ route('home') }}" 
                   class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50 {{ request()->routeIs('home') ? 'sidebar-active' : '' }}">
                    <span class="material-symbols-outlined text-2xl mr-4">home</span>
                    <span class="font-medium">Home</span>
                </a>

                <!-- Categories Link -->
                <a href="{{ route('categories') }}" 
                   class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50 {{ request()->routeIs('categories') || request()->routeIs('categories.*') ? 'sidebar-active' : '' }}">
                    <span class="material-symbols-outlined text-2xl mr-4">shoppingmode</span>
                    <span class="font-medium">Categories</span>
                </a>

                <!-- Bookings Link -->
                <a href="{{ route('bookings') }}" 
                   class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50 {{ request()->routeIs('bookings') ? 'sidebar-active' : '' }}">
                    <span class="material-symbols-outlined text-2xl mr-4">calendar_month</span>
                    <span class="font-medium">Bookings</span>
                </a>

                <!-- Settings Link -->
                <a href="{{ route('settings') }}" 
                   class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50 {{ request()->routeIs('settings') ? 'sidebar-active' : '' }}">
                    <span class="material-symbols-outlined text-2xl mr-4">settings</span>
                    <span class="font-medium">Settings</span>
                </a>
            </div>

            <!-- Logout -->
            <div class="mb-6">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center px-6 py-3 text-gray-600 hover:bg-red-50 w-full text-left">
                        <span class="material-symbols-outlined text-2xl mr-4">logout</span>
                        <span class="font-medium">Logout</span>
                    </button>
                </form>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 overflow-x-hidden overflow-y-auto">

        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-6 py-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Welcome to Agapay!</h2>
                    <p class="text-sm text-gray-600">Your trusted barangay marketplace</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                        🏆 {{ $userStats['suki_points'] ?? '0' }} Suki Points
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-6">
            @yield('content') <!-- Dynamic content area will be injected here -->
        </main>

    </div>
</div>

</body>
</html>
