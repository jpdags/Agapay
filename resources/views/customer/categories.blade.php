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
                <p class="text-xs text-gray-500 mt-2">{{ count($products ?? []) }} available</p>
            </div>

            <!-- SERVICES -->
            <div onclick="selectCategory('services')" class="cursor-pointer bg-white shadow-md rounded-lg p-6 border hover:border-dark-red hover:shadow-lg transition group">
                <span class="material-symbols-outlined text-5xl text-dark-red mb-4">handyman</span>
                <h3 class="text-xl font-semibold text-gray-800 group-hover:text-dark-red">Services</h3>
                <p class="text-sm text-gray-600 mt-1">Skilled local labor: electricians, plumbers, carpenters, mechanics</p>
                <p class="text-xs text-gray-500 mt-2">{{ count($services ?? []) }} available</p>
            </div>

            <!-- ENTREPRENEURSHIP -->
            <div onclick="selectCategory('entrepreneurship')" class="cursor-pointer bg-white shadow-md rounded-lg p-6 border hover:border-dark-red hover:shadow-lg transition group">
                <span class="material-symbols-outlined text-5xl text-dark-red mb-4">storefront</span>
                <h3 class="text-xl font-semibold text-gray-800 group-hover:text-dark-red">Entrepreneurship</h3>
                <p class="text-sm text-gray-600 mt-1">Home-based barangay businesses and food producers</p>
                <p class="text-xs text-gray-500 mt-2">{{ count($businesses ?? []) }} available</p>
            </div>
        </div>

        <!-- Dynamic List of What's Offered -->
        <div id="categoryDisplay" class="hidden bg-white shadow-lg rounded-lg p-6 border">
            <h2 id="categoryTitle" class="text-xl font-bold text-gray-800 mb-4"></h2>
            <div id="itemsContainer" class="space-y-4"></div>
        </div>
    </div>

    <!-- Hidden data elements for JavaScript -->
    <script type="application/json" id="products-data">{!! json_encode($products ?? []) !!}</script>
    <script type="application/json" id="services-data">{!! json_encode($services ?? []) !!}</script>
    <script type="application/json" id="businesses-data">{!! json_encode($businesses ?? []) !!}</script>

    <script>
        // Pass PHP data to JavaScript
        const productsData = JSON.parse(document.getElementById('products-data').textContent);
        const servicesData = JSON.parse(document.getElementById('services-data').textContent);
        const businessesData = JSON.parse(document.getElementById('businesses-data').textContent);

        function selectCategory(type) {
            const display = document.getElementById('categoryDisplay');
            const title = document.getElementById('categoryTitle');
            const items = document.getElementById('itemsContainer');

            display.classList.remove('hidden');

            let data = [];
            let titleText = '';

            if (type === 'products') {
                titleText = "Available Products";
                data = productsData;
            }

            if (type === 'services') {
                titleText = "Available Services";
                data = servicesData;
            }

            if (type === 'entrepreneurship') {
                titleText = "Support Local Businesses";
                data = businessesData;
            }

            title.innerText = titleText;

            // Render items or show "no data" message
            if (data.length === 0) {
                items.innerHTML = '<p class="text-gray-500 text-center py-4">There are no ' + type + ' available at the moment.</p>';
            } else {
                items.innerHTML = data.map(item => {
                    if (type === 'products') {
                        const totalPrice = parseFloat(item.price) + (parseFloat(item.delivery_fee) || 0);
                        return `
                            <div class="p-4 border rounded-lg hover:shadow-md transition">
                                <h3 class="text-lg font-semibold text-dark-red">${item.name}</h3>
                                ${item.brand ? `<p class="text-sm text-gray-700">Brand: ${item.brand}</p>` : ''}
                                <p class="text-sm font-medium text-green-600 mt-2">₱${parseFloat(item.price).toFixed(2)}</p>
                                ${item.delivery_fee ? `<p class="text-xs text-gray-500">Delivery Fee: ₱${parseFloat(item.delivery_fee).toFixed(2)}</p>` : ''}
                                <p class="text-sm font-semibold text-gray-800 mt-1">Total: ₱${totalPrice.toFixed(2)}</p>
                                ${item.user ? `<p class="text-xs text-gray-500 mt-1">Provider: ${item.user.name}</p>` : ''}
                                <button onclick="openBookingModal('product', ${item.id}, ${item.user?.id || 0}, '${item.name}', ${item.price}, ${item.delivery_fee || 0})" class="mt-3 w-full bg-dark-red text-white px-4 py-2 rounded hover:bg-red-800 transition">Book Now</button>
                            </div>
                        `;
                    } else if (type === 'services') {
                        return `
                            <div class="p-4 border rounded-lg hover:shadow-md transition">
                                <h3 class="text-lg font-semibold text-dark-red">${item.name}</h3>
                                <p class="text-sm text-gray-700">${item.description || ''}</p>
                                <p class="text-sm font-medium text-green-600 mt-2">Rate: ₱${parseFloat(item.rate).toFixed(2)}</p>
                                ${item.user ? `<p class="text-xs text-gray-500 mt-1">Provider: ${item.user.name}</p>` : ''}
                                <button onclick="openBookingModal('service', ${item.id}, ${item.user?.id || 0}, '${item.name}', ${item.rate})" class="mt-3 w-full bg-dark-red text-white px-4 py-2 rounded hover:bg-red-800 transition">Book Service</button>
                            </div>
                        `;
                    } else if (type === 'entrepreneurship') {
                        return `
                            <div class="p-4 border rounded-lg hover:shadow-md transition">
                                <h3 class="text-lg font-semibold text-dark-red">${item.name}</h3>
                                <p class="text-sm text-gray-700">${item.description || ''}</p>
                                <p class="text-xs text-gray-600 mt-1">Category: ${item.category || 'N/A'}</p>
                                <p class="text-xs text-gray-600">Contact: ${item.contact || 'N/A'}</p>
                                ${item.user ? `<p class="text-xs text-gray-500 mt-1">Owner: ${item.user.name}</p>` : ''}
                                <button onclick="openBookingModal('business', ${item.id}, ${item.user?.id || 0}, '${item.name}')" class="mt-3 w-full bg-dark-red text-white px-4 py-2 rounded hover:bg-red-800 transition">Contact/Inquire</button>
                            </div>
                        `;
                    }
                }).join('');
            }
        }

        // Auto-select category from URL parameter on page load
        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const category = urlParams.get('category');
            
            if (category && ['products', 'services', 'entrepreneurship'].includes(category)) {
                selectCategory(category);
                // Scroll to the category display
                setTimeout(() => {
                    document.getElementById('categoryDisplay').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);
            }
        });

        function openBookingModal(type, offeringId, providerId, offeringName, price, deliveryFee) {
            const modal = document.getElementById('bookingModal');
            const form = document.getElementById('bookingForm');
            const title = document.getElementById('bookingTitle');
            
            title.textContent = `Book ${offeringName}`;
            form.action = '{{ route("order.create") }}';
            
            // Set hidden fields
            form.querySelector('input[name="offering_type"]').value = type;
            form.querySelector('input[name="offering_id"]').value = offeringId;
            form.querySelector('input[name="provider_id"]').value = providerId;
            
            // Update form fields based on type
            const quantityField = form.querySelector('input[name="quantity"]').parentElement;
            const scheduledDateField = form.querySelector('input[name="scheduled_date"]').parentElement;
            const scheduledTimeField = form.querySelector('input[name="scheduled_time"]').parentElement;
            
            if (type === 'product') {
                quantityField.style.display = 'block';
                scheduledDateField.style.display = 'block';
                scheduledTimeField.style.display = 'block';
                const totalAmount = (price || 0) + (deliveryFee || 0);
                form.querySelector('input[name="total_amount"]').value = totalAmount.toFixed(2);
            } else if (type === 'service') {
                quantityField.style.display = 'none';
                scheduledDateField.style.display = 'block';
                scheduledTimeField.style.display = 'block';
                form.querySelector('input[name="total_amount"]').value = (price || 0).toFixed(2);
            } else if (type === 'business') {
                quantityField.style.display = 'none';
                scheduledDateField.style.display = 'none';
                scheduledTimeField.style.display = 'none';
                form.querySelector('input[name="total_amount"]').value = '0.00';
            }
            
            modal.classList.remove('hidden');
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
            document.getElementById('bookingForm').reset();
        }
    </script>

    <!-- Booking Modal -->
    <div id="bookingModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 id="bookingTitle" class="text-lg font-bold text-gray-900 mb-4"></h3>
                <form id="bookingForm" method="POST" action="{{ route('order.create') }}">
                    @csrf
                    <input type="hidden" name="offering_type" value="">
                    <input type="hidden" name="offering_id" value="">
                    <input type="hidden" name="provider_id" value="">
                    <input type="hidden" name="total_amount" value="0">
                    
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">Quantity</label>
                        <input type="number" name="quantity" value="1" min="1" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">Scheduled Time</label>
                        <input type="time" name="scheduled_time" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm text-gray-600 mb-1">Notes/Requirements</label>
                        <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red" placeholder="Any special requirements or notes..."></textarea>
                    </div>
                    
                    <div class="flex gap-2 mt-4">
                        <button type="submit" class="flex-1 bg-dark-red text-white px-4 py-2 rounded hover:bg-red-800 transition">Submit Booking</button>
                        <button type="button" onclick="closeBookingModal()" class="flex-1 bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 transition">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
