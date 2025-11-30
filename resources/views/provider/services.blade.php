@extends('layouts.provider-main')

@section('content')

<h1 class="text-2xl font-bold text-dark-red mb-4">My Offerings</h1>
<p class="text-gray-600 mb-6">Manage your products, services, and business offerings</p>

<!-- Tabs for different offering types -->
<div class="mb-6">
    <div class="flex space-x-2 border-b border-gray-200">
        <button onclick="showTab('products')" id="tab-products" class="px-4 py-2 font-medium text-gray-700 border-b-2 border-dark-red tab-button active">
            Products ({{ count($products ?? []) }})
        </button>
        <button onclick="showTab('services')" id="tab-services" class="px-4 py-2 font-medium text-gray-500 hover:text-gray-700 tab-button">
            Services ({{ count($services ?? []) }})
        </button>
        <button onclick="showTab('businesses')" id="tab-businesses" class="px-4 py-2 font-medium text-gray-500 hover:text-gray-700 tab-button">
            Businesses ({{ count($businesses ?? []) }})
        </button>
    </div>
</div>

<!-- Add New Buttons -->
<div class="mb-4 flex gap-2">
    <button onclick="openAddModal('product')" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
        + Add Product
    </button>
    <button onclick="openAddModal('service')" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
        + Add Service
    </button>
    <button onclick="openAddModal('business')" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
        + Add Business
    </button>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Products Tab -->
<div id="content-products" class="tab-content">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">My Products</h2>
    <div class="space-y-4">
        @forelse($products ?? [] as $product)
            <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $product->name }}</h2>
                        @if($product->brand)
                            <p class="text-sm text-gray-600">Brand: {{ $product->brand }}</p>
                        @endif
                        <p class="text-green-600 font-medium mt-1">Price: ₱{{ number_format($product->price, 2) }}</p>
                        @if($product->delivery_fee)
                            <p class="text-xs text-gray-500">Delivery Fee: ₱{{ number_format($product->delivery_fee, 2) }}</p>
                        @endif
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button data-type="product" data-id="{{ $product->id }}" onclick="openEditModal(this.dataset.type, parseInt(this.dataset.id))" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Edit</button>
                    <form action="{{ route('provider.offering.delete', ['type' => 'product', 'id' => $product->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">Remove</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white p-5 rounded shadow border">
                <p class="text-gray-500 text-center">No products added yet.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Services Tab -->
<div id="content-services" class="tab-content hidden">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">My Services</h2>
    <div class="space-y-4">
        @forelse($services ?? [] as $service)
            <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $service->name }}</h2>
                        <p class="text-sm text-gray-700">{{ $service->description }}</p>
                        <p class="text-green-600 font-medium mt-1">Rate: ₱{{ number_format($service->rate, 2) }}</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button data-type="service" data-id="{{ $service->id }}" onclick="openEditModal(this.dataset.type, parseInt(this.dataset.id))" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Edit</button>
                    <form action="{{ route('provider.offering.delete', ['type' => 'service', 'id' => $service->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this service?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">Remove</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white p-5 rounded shadow border">
                <p class="text-gray-500 text-center">No services added yet.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Businesses Tab -->
<div id="content-businesses" class="tab-content hidden">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">My Businesses</h2>
    <div class="space-y-4">
        @forelse($businesses ?? [] as $business)
            <div class="bg-white p-5 rounded shadow border hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-lg font-semibold">{{ $business->name }}</h2>
                        <p class="text-sm text-gray-700">{{ $business->description }}</p>
                        <p class="text-xs text-gray-600 mt-1">Category: {{ $business->category }}</p>
                        <p class="text-xs text-gray-600">Contact: {{ $business->contact }}</p>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button data-type="business" data-id="{{ $business->id }}" onclick="openEditModal(this.dataset.type, parseInt(this.dataset.id))" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Edit</button>
                    <form action="{{ route('provider.offering.delete', ['type' => 'business', 'id' => $business->id]) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this business?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition">Remove</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white p-5 rounded shadow border">
                <p class="text-gray-500 text-center">No businesses added yet.</p>
            </div>
        @endforelse
    </div>
</div>

<style>
    .tab-button.active {
        color: #8B0000;
        border-bottom-color: #8B0000;
    }
    .tab-content {
        display: block;
    }
    .tab-content.hidden {
        display: none;
    }
</style>

<script>
    function showTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.add('hidden');
        });
        
        // Remove active class from all tabs
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
            button.classList.add('text-gray-500');
            button.classList.remove('text-gray-700');
        });
        
        // Show selected tab content
        document.getElementById('content-' + tabName).classList.remove('hidden');
        
        // Add active class to selected tab
        const activeTab = document.getElementById('tab-' + tabName);
        activeTab.classList.add('active');
        activeTab.classList.remove('text-gray-500');
        activeTab.classList.add('text-gray-700');
    }
</script>

<!-- Modal for Add/Edit Forms -->
<div id="offeringModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-900 mb-4"></h3>
            <form id="offeringForm" method="POST">
                @csrf
                <div id="formFields"></div>
                <div class="flex gap-2 mt-4">
                    <button type="submit" class="flex-1 bg-dark-red text-white px-4 py-2 rounded hover:bg-red-800 transition">Save</button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400 transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden data elements for JavaScript -->
<script type="application/json" id="products-data">{!! json_encode($products ?? []) !!}</script>
<script type="application/json" id="services-data">{!! json_encode($services ?? []) !!}</script>
<script type="application/json" id="businesses-data">{!! json_encode($businesses ?? []) !!}</script>

<script>
    const offeringsData = {
        products: JSON.parse(document.getElementById('products-data').textContent),
        services: JSON.parse(document.getElementById('services-data').textContent),
        businesses: JSON.parse(document.getElementById('businesses-data').textContent)
    };

    function openAddModal(type) {
        // Map type to tab name (handle special case for business -> businesses)
        const tabMap = {
            'product': 'products',
            'service': 'services',
            'business': 'businesses'
        };
        showTab(tabMap[type] || type + 's');
        const modal = document.getElementById('offeringModal');
        const form = document.getElementById('offeringForm');
        const fields = document.getElementById('formFields');
        const title = document.getElementById('modalTitle');
        const submitBtn = form.querySelector('button[type="submit"]');

        // Clear any existing hidden type input
        const existingTypeInput = form.querySelector('input[name="type"]');
        if (existingTypeInput) {
            existingTypeInput.remove();
        }

        form.action = '{{ route("provider.offering.store") }}';
        form.method = 'POST';
        
        // Add hidden type input
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = type;
        form.insertBefore(typeInput, fields);

        // Update submit button text
        if (submitBtn) {
            submitBtn.textContent = 'Save';
        }

        if (type === 'product') {
            title.textContent = 'Add New Product';
            fields.innerHTML = `
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Product Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Brand</label>
                    <input type="text" name="brand" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red" placeholder="e.g., Petron, Shell">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Price (₱) *</label>
                    <input type="number" name="price" step="0.01" min="0" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Delivery Fee (₱)</label>
                    <input type="number" name="delivery_fee" step="0.01" min="0" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
            `;
        } else if (type === 'service') {
            title.textContent = 'Add New Service';
            fields.innerHTML = `
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Service Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red" placeholder="e.g., Plumbing, Electrical Work">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Description *</label>
                    <textarea name="description" required rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red" placeholder="Describe your service..."></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Rate (₱) *</label>
                    <input type="number" name="rate" step="0.01" min="0" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red" placeholder="Service rate per hour/day">
                </div>
            `;
        } else if (type === 'business') {
            title.textContent = 'Add New Business';
            fields.innerHTML = `
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Business Name *</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Category *</label>
                    <input type="text" name="category" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red" placeholder="e.g., Food, Crafts, Retail">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Contact *</label>
                    <input type="text" name="contact" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red" placeholder="Phone number or email">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Description *</label>
                    <textarea name="description" required rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red" placeholder="Describe your business..."></textarea>
                </div>
            `;
        }

        modal.classList.remove('hidden');
    }

    function openEditModal(type, id) {
        // Map type to tab name (handle special case for business -> businesses)
        const tabMap = {
            'product': 'products',
            'service': 'services',
            'business': 'businesses'
        };
        showTab(tabMap[type] || type + 's');
        const modal = document.getElementById('offeringModal');
        const form = document.getElementById('offeringForm');
        const fields = document.getElementById('formFields');
        const title = document.getElementById('modalTitle');
        const submitBtn = form.querySelector('button[type="submit"]');

        const data = offeringsData[type + 's'].find(item => item.id === id);
        if (!data) return;

        // Clear any existing method spoofing and type input
        const existingMethod = form.querySelector('input[name="_method"]');
        if (existingMethod) {
            existingMethod.remove();
        }
        const existingTypeInput = form.querySelector('input[name="type"]');
        if (existingTypeInput) {
            existingTypeInput.remove();
        }

        form.action = '{{ route("provider.offering.update", ["type" => ":type", "id" => ":id"]) }}'.replace(':type', type).replace(':id', id);
        form.method = 'POST';
        
        // Add method spoofing for PUT
        const methodInput = document.createElement('input');
        methodInput.type = 'hidden';
        methodInput.name = '_method';
        methodInput.value = 'PUT';
        form.insertBefore(methodInput, fields);

        // Add hidden type input
        const typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        typeInput.value = type;
        form.insertBefore(typeInput, fields);

        // Update submit button text
        if (submitBtn) {
            submitBtn.textContent = 'Update';
        }

        if (type === 'product') {
            title.textContent = 'Edit Product';
            fields.innerHTML = `
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Product Name *</label>
                    <input type="text" name="name" value="${data.name || ''}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Brand</label>
                    <input type="text" name="brand" value="${data.brand || ''}" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Price (₱) *</label>
                    <input type="number" name="price" value="${data.price || ''}" step="0.01" min="0" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Delivery Fee (₱)</label>
                    <input type="number" name="delivery_fee" value="${data.delivery_fee || ''}" step="0.01" min="0" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
            `;
        } else if (type === 'service') {
            title.textContent = 'Edit Service';
            fields.innerHTML = `
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Service Name *</label>
                    <input type="text" name="name" value="${data.name || ''}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Description *</label>
                    <textarea name="description" required rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">${data.description || ''}</textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Rate (₱) *</label>
                    <input type="number" name="rate" value="${data.rate || ''}" step="0.01" min="0" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
            `;
        } else if (type === 'business') {
            title.textContent = 'Edit Business';
            fields.innerHTML = `
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Business Name *</label>
                    <input type="text" name="name" value="${data.name || ''}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Category *</label>
                    <input type="text" name="category" value="${data.category || ''}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Contact *</label>
                    <input type="text" name="contact" value="${data.contact || ''}" required class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                </div>
                <div class="mb-4">
                    <label class="block text-sm text-gray-600 mb-1">Description *</label>
                    <textarea name="description" required rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">${data.description || ''}</textarea>
                </div>
            `;
        }

        modal.classList.remove('hidden');
    }

    function closeModal() {
        const modal = document.getElementById('offeringModal');
        const form = document.getElementById('offeringForm');
        const fields = document.getElementById('formFields');
        
        // Clear form fields
        fields.innerHTML = '';
        
        // Remove any dynamically added inputs
        const typeInput = form.querySelector('input[name="type"]');
        if (typeInput && !typeInput.hasAttribute('data-static')) {
            typeInput.remove();
        }
        const methodInput = form.querySelector('input[name="_method"]');
        if (methodInput) {
            methodInput.remove();
        }
        
        modal.classList.add('hidden');
    }
</script>

@endsection
