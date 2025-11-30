<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agapay Provider</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        .bg-dark-red { background-color: #8B0000; }
        .text-dark-red { color: #8B0000; }
        .sidebar-link { transition: 0.2s; }
        .sidebar-link:hover { background: #FEE2E2; }
        .sidebar-active { background: #FEE2E2; border-right: 4px solid #8B0000; font-weight: bold; }
    </style>
</head>
<body class="bg-gray-100">

<div class="flex h-screen">

    <!-- Sidebar -->
    <div class="w-64 bg-white shadow-lg flex flex-col">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-dark-red">Agapay</h1>
            <p class="text-sm text-gray-600">Suki Provider</p>
        </div>

        <nav class="flex flex-col flex-1">

            <a href="{{ route('dashboard.provider') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('dashboard.provider') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined mr-4">dashboard</span>
                Dashboard
            </a>

            <a href="{{ route('provider.requests') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('provider.requests') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined mr-4">assignment</span>
                Requests
            </a>

            <a href="{{ route('provider.services') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('provider.services') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined mr-4">store</span>
                My Offerings
            </a>

            <a href="{{ route('provider.schedule') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('provider.schedule') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined mr-4">calendar_month</span>
                Schedule
            </a>

            <a href="{{ route('provider.verification') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('provider.verification') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined mr-4">verified</span>
                Verification
            </a>

        </nav>

        <form action="{{ route('logout') }}" method="POST" class="p-6">
            @csrf
            <button class="flex items-center w-full px-6 py-3 text-gray-600 hover:bg-red-50">
                <span class="material-symbols-outlined mr-4">logout</span>
                Logout
            </button>
        </form>
    </div>

    <!-- Main -->
    <div class="flex-1 overflow-y-auto">

        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="px-6 py-4 flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        Welcome, {{ $provider->name ?? 'Provider' }}
                    </h2>
                    <p class="text-sm text-gray-600">Manage your Agapay services</p>
                </div>

                <div class="flex items-center space-x-4">
                    @if(isset($provider->is_verified))
                        <span class="px-3 py-1 text-sm rounded-full 
                            {{ $provider->is_verified ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $provider->is_verified ? 'Verified' : 'Not Verified' }}
                        </span>
                    @endif

                    <img src="https://ui-avatars.com/api/?name=Provider" class="w-9 h-9 rounded-full" />
                </div>
            </div>
        </header>

        <!-- Dynamic Page Content -->
        <main class="p-6">
            @yield('content')
        </main>

    </div>
</div>

</body>
</html>
