@extends('layouts.provider-main')

@section('content')

<div class="max-w-4xl mx-auto p-6">

    <h1 class="text-2xl font-bold text-dark-red mb-4">Settings</h1>
    <p class="text-gray-600 mb-6">Manage your account and personal details</p>

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

    <!-- Profile Section -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h2>

        <form action="{{ route('provider.verification.update') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="flex items-center gap-4 mb-4">
                @if($provider->photo)
                    <img id="photo-preview" class="w-16 h-16 rounded-full object-cover"
                        src="{{ asset('storage/photos/' . $provider->photo) }}"
                        alt="Profile Photo">
                @else
                    <img id="photo-preview" class="w-16 h-16 rounded-full object-cover"
                        src="https://ui-avatars.com/api/?name={{ urlencode($provider->name) }}&background=DC2626&color=fff"
                        alt="Default Photo">
                @endif
                <div>
                    <label for="photo" class="block px-4 py-2 bg-dark-red text-white rounded hover:bg-red-800 cursor-pointer text-center">
                        Change Photo
                    </label>
                    <input type="file" id="photo" name="photo" accept="image/*" class="hidden" onchange="previewPhoto(this)">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-sm text-gray-600 mb-1">Full Name</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $provider->name) }}"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red"
                        required>
                </div>

                <div>
                    <label for="email" class="block text-sm text-gray-600 mb-1">Email</label>
                    <input type="email" id="email" value="{{ $provider->email }}"
                        class="w-full px-3 py-2 border rounded-lg bg-gray-100 cursor-not-allowed"
                        disabled>
                    <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                </div>

                <div>
                    <label for="phone" class="block text-sm text-gray-600 mb-1">Phone Number</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $provider->phone) }}"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red"
                        placeholder="e.g., 09123456789">
                </div>

                <div>
                    <label for="address" class="block text-sm text-gray-600 mb-1">Address</label>
                    <input type="text" id="address" name="address" value="{{ old('address', $provider->address) }}"
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red"
                        placeholder="e.g., 123 Main St, Barangay Name">
                </div>
            </div>

            <button type="submit" class="mt-4 px-5 py-2 bg-dark-red text-white rounded hover:bg-red-800 transition">
                Save Changes
            </button>
        </form>
    </div>

    <!-- Verification Status Section -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Verification Status</h2>

        <p class="text-gray-700">
        <strong>Status:</strong>
            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 text-sm rounded-full ml-2">
                Pending Verification
        </span>
    </p>
        <p class="text-gray-500 text-sm mt-1">Your account is being reviewed for verification.</p>

        <div class="mt-4">
            <h3 class="text-md font-semibold text-gray-800 mb-2">Verification Requirements</h3>
            <ul class="list-disc ml-5 text-gray-700 space-y-1">
                <li>Valid ID (Government-issued)</li>
                <li>Barangay Certificate</li>
                <li>Business Permit (if applicable)</li>
    </ul>
        </div>

        <button class="mt-4 px-4 py-2 bg-dark-red text-white rounded hover:bg-red-800 transition">
            Upload Verification Documents
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

<script>
    function previewPhoto(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('photo-preview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

@endsection
