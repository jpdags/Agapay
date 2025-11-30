@extends('layouts.customer-main')

@section('content')
    <div class="max-w-6xl mx-auto p-6">
        <h1 class="text-2xl font-bold text-dark-red mb-4">Categories</h1>
        <p class="text-gray-600 mb-6">Choose a category to explore barangay-offered services and goods</p>

        <!-- Category Options -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- PRODUCTS -->
            <div onclick="selectCategory('products')" class="cursor-pointer bg-white shadow-md rounded-lg p-6 border hover:border-dark-red hover:shadow-lg transition group">
                <span class="material-symbols-outlined text-5xl text-dark-red mb-4">inventory_2</span>
                <h3 class="text-xl font-semibold text-gray-800 group-hover:text-dark-red">Products</h3>
                <p class="text-sm text-gray-600 mt-1">Barangay-offered goods like LPG, water jugs, and essentials</p>
            </div>

            <!-- SERVICES -->
            <div onclick="selectCategory('services')" class="cursor-pointer bg-white shadow-md rounded-lg p-6 border hover:border-dark-red hover:shadow-lg transition group">
                <span class="material-symbols-outlined text-5xl text-dark-red mb-4">handyman</span>
                <h3 class="text-xl font-semibold text-gray-800 group-hover:text-dark-red">Services</h3>
                <p class="text-sm text-gray-600 mt-1">Skilled local labor: electricians, plumbers, carpenters, mechanics</p>
            </div>

            <!-- ENTREPRENEURSHIP -->
            <div onclick="selectCategory('entrepreneurship')" class="cursor-pointer bg-white shadow-md rounded-lg p-6 border hover:border-dark-red hover:shadow-lg transition group">
                <span class="material-symbols-outlined text-5xl text-dark-red mb-4">storefront</span>
                <h3 class="text-xl font-semibold text-gray-800 group-hover:text-dark-red">Entrepreneurship</h3>
                <p class="text-sm text-gray-600 mt-1">Home-based barangay businesses and food producers</p>
            </div>
        </div>

        <!-- Dynamic List of What’s Offered -->
        <div id="categoryDisplay" class="hidden bg-white shadow-lg rounded-lg p-6 border">
            <h2 id="categoryTitle" class="text-xl font-bold text-gray-800 mb-4"></h2>

            <div id="itemsContainer" class="space-y-4"></div>
        </div>
    </div>

    <script>
        function selectCategory(type) {
            const display = document.getElementById('categoryDisplay');
            const title = document.getElementById('categoryTitle');
            const items = document.getElementById('itemsContainer');

            display.classList.remove('hidden');

            let data = [];

            if (type === 'products') {
                title.innerText = "Available Products";
                data = [
                    { name: "LPG Gas Tank", description: "Petron, Shell, PryceGas", price: "₱930 - ₱950" },
                    { name: "Water Gallon (5-gallon)", description: "Refilling stations near you", price: "₱25 - ₱40" },
                ];
            }

            if (type === 'services') {
                title.innerText = "Available Services";
                data = [
                    { name: "Electrician", description: "Wiring • Outlet Installation • Repair", price: "Starts at ₱300" },
                    { name: "Plumber", description: "Leaks • Faucets • Toilets", price: "Starts at ₱350" },
                    { name: "Carpenter", description: "Repairs • Custom Woodwork", price: "Starts at ₱400" },
                ];
            }

            if (type === 'entrepreneurship') {
                title.innerText = "Support Local Businesses";
                data = [
                    { name: "Mango Float", description: "Home-made desserts by neighbors", price: "₱250 per tray" },
                    { name: "Biko & Kakanin", description: "Freshly cooked local delicacies", price: "₱50 - ₱200" },
                    { name: "Pancit", description: "For occasions and gatherings", price: "₱120 - ₱500" },
                ];
            }

            // render the items
            items.innerHTML = data.map(item => `
                <div class="p-4 border rounded-lg hover:shadow-md transition">
                    <h3 class="text-lg font-semibold text-dark-red">${item.name}</h3>
                    <p class="text-sm text-gray-700">${item.description}</p>
                    <p class="text-sm font-medium text-green-600 mt-2">${item.price}</p>
                </div>
            `).join('');
        }
    </script>

@endsection
