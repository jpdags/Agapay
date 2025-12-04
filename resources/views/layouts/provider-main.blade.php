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
        .bg-light-red { background-color: #DC2626; }
        .text-dark-red { color: #8B0000; }
        .border-dark-red { border-color: #8B0000; }
        
        .sidebar-link { 
            transition: all 0.25s ease; 
            color: #4B5563;
        }
        .sidebar-link:hover { 
            background-color: #FEE2E2; 
            transform: translateX(4px);
        }
        .sidebar-active { 
            background-color: #FEE2E2; 
            border-right: 4px solid #8B0000; 
            font-weight: 600;
            color: #8B0000 !important;
        }
        
        body {
            background-color: #F3F4F6 !important;
        }
    </style>
</head>
<body class="bg-gray-100" style="background-color: #F3F4F6;">

<div class="flex h-screen">

    <!-- Sidebar -->
    <div class="w-64 bg-white shadow-lg flex flex-col">
        <div class="p-6">
            <h1 class="text-2xl font-bold text-dark-red">Agapay</h1>
            <p class="text-sm text-gray-600">Suki Provider</p>
        </div>

        <nav class="flex flex-col flex-1 mt-6">

            <a href="{{ route('dashboard.provider') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('dashboard.provider') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined text-2xl mr-4">dashboard</span>
                <span class="font-medium">Dashboard</span>
            </a>

            <a href="{{ route('provider.requests') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('provider.requests') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined text-2xl mr-4">assignment</span>
                <span class="font-medium">Requests</span>
            </a>

            <a href="{{ route('provider.services') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('provider.services') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined text-2xl mr-4">store</span>
                <span class="font-medium">My Offerings</span>
            </a>

            <a href="{{ route('provider.schedule') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('provider.schedule') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined text-2xl mr-4">calendar_month</span>
                <span class="font-medium">Schedule</span>
            </a>

            <a href="{{ route('provider.verification') }}" 
               class="flex items-center px-6 py-3 sidebar-link {{ request()->routeIs('provider.verification') ? 'sidebar-active' : '' }}">
                <span class="material-symbols-outlined text-2xl mr-4">verified</span>
                <span class="font-medium">Verification</span>
            </a>

        </nav>

        <div class="mb-6">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center w-full px-6 py-3 text-gray-600 hover:bg-red-50 sidebar-link">
                    <span class="material-symbols-outlined text-2xl mr-4">logout</span>
                    <span class="font-medium">Logout</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Main -->
    <div class="flex-1 overflow-x-hidden overflow-y-auto" style="background-color: #F3F4F6;">

        <!-- Header -->
        <header class="bg-white shadow-sm" style="background-color: #FFFFFF;">
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

                    <img src="https://ui-avatars.com/api/?name={{ urlencode($provider->name ?? 'Provider') }}&background=DC2626&color=fff" 
                         class="w-9 h-9 rounded-full object-cover" 
                         alt="Provider" />
                </div>
            </div>
        </header>

        <!-- Dynamic Page Content -->
        <main class="p-6" style="background-color: #F3F4F6; min-height: calc(100vh - 80px);">
            @yield('content')
        </main>

    </div>
</div>

</body>
</html>
