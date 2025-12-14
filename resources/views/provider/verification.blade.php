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

    @if(session('warning'))
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <p class="font-semibold">⚠️ Important:</p>
            <p>{{ session('warning') }}</p>
        </div>
    @endif

    @if(empty($provider->phone) || empty($provider->address))
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 px-4 py-3 rounded mb-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-600" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="font-semibold">Account Verification Required</p>
                    <p class="mt-1 text-sm">
                        Your account is not fully verified. Please complete your profile by adding your 
                        @if(empty($provider->phone) && empty($provider->address))
                            <strong>phone number and address</strong>
                        @elseif(empty($provider->phone))
                            <strong>phone number</strong>
                        @else
                            <strong>address</strong>
                        @endif
                        below to verify your account.
                    </p>
                </div>
            </div>
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

        <form action="{{ route('provider.settings.update') }}" method="POST" enctype="multipart/form-data">
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

    </div>

    <!-- Account Settings -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Account Settings</h2>

        <div class="space-y-4">
            <button onclick="togglePasswordForm()" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Change Password
            </button>

            <div id="passwordForm" class="hidden mt-4 p-4 bg-gray-50 rounded border">
                <form action="{{ route('password.change') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label for="current_password" class="block text-sm text-gray-700 mb-1">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block text-sm text-gray-700 mb-1">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                    </div>
                    <div class="mb-4">
                        <label for="new_password_confirmation" class="block text-sm text-gray-700 mb-1">Confirm New Password</label>
                        <input type="password" id="new_password_confirmation" name="new_password_confirmation" required minlength="8"
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-dark-red">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-dark-red text-white rounded hover:bg-red-800 transition">
                            Update Password
                        </button>
                        <button type="button" onclick="togglePasswordForm()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 p-4 rounded border">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700">Email Notifications</span>
                    <form action="{{ route('notifications.toggle') }}" method="POST" class="inline">
                        @csrf
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="enabled" value="1" 
                                {{ $provider->notifications_enabled ? 'checked' : '' }}
                                onchange="this.form.submit()"
                                class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-dark-red rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-dark-red"></div>
                        </label>
                    </form>
                </div>
                <p class="text-xs text-gray-500">
                    {{ $provider->notifications_enabled ? 'You will receive email notifications for account activities.' : 'Email notifications are currently disabled.' }}
                </p>
            </div>

            <button onclick="toggleDeleteForm()" class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                Delete Account
            </button>

            <div id="deleteForm" class="hidden mt-4 p-4 bg-red-50 rounded border border-red-200">
                <p class="text-sm text-red-800 font-semibold mb-3">⚠️ Warning: This action cannot be undone!</p>
                <p class="text-sm text-red-700 mb-4">Deleting your account will permanently remove all your data including bookings, orders, and profile information.</p>
                <form action="{{ route('account.delete') }}" method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!');">
                    @csrf
                    @method('DELETE')
                    <div class="mb-4">
                        <label for="delete_password" class="block text-sm text-gray-700 mb-1">Enter your password to confirm:</label>
                        <input type="password" id="delete_password" name="password" required
                            class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                            Confirm Delete Account
                        </button>
                        <button type="button" onclick="toggleDeleteForm()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
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

    function togglePasswordForm() {
        const form = document.getElementById('passwordForm');
        form.classList.toggle('hidden');
    }

    function toggleDeleteForm() {
        const form = document.getElementById('deleteForm');
        form.classList.toggle('hidden');
    }
</script>

@endsection
