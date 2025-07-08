@extends('layouts.a                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300">Name</label>
                        <input type="text" id="name" name="full_name" value="{{ auth()->user()->full_name }}"
                               class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
@section('title', 'Profile')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">User Profile</h1>
        <p class="text-gray-300 mt-2">Manage your account settings and preferences</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Profile Information -->
        <div class="lg:col-span-2">
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h2 class="text-xl font-semibold text-white mb-4">Profile Information</h2>

                <form class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300">Name</label>
                        <input type="text" id="name" name="name" value="{{ auth()->user()->name }}"
                               class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300">Email</label>
                        <input type="email" id="email" name="email" value="{{ auth()->user()->email }}"
                               class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-300">Current Password</label>
                        <input type="password" id="current_password" name="current_password"
                               class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-300">New Password</label>
                        <input type="password" id="new_password" name="new_password"
                               class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-300">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Account Stats -->
        <div class="space-y-6">
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h3 class="text-lg font-semibold text-white mb-4">Account Information</h3>

                <div class="space-y-3">
                    <div>
                        <span class="text-gray-300">Member Since</span>
                        <div class="text-white font-medium">{{ auth()->user()->created_at->format('M d, Y') }}</div>
                    </div>

                    <div>
                        <span class="text-gray-300">Last Login</span>
                        <div class="text-white font-medium">{{ auth()->user()->last_login_at ? auth()->user()->last_login_at->diffForHumans() : 'Never' }}</div>
                    </div>

                    <div>
                        <span class="text-gray-300">Status</span>
                        <div class="text-white font-medium">
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if(auth()->user()->status === 'active') bg-green-900 text-green-300
                                @else bg-red-900 text-red-300 @endif">
                                {{ ucfirst(auth()->user()->status) }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-300">Role</span>
                        <div class="text-white font-medium">
                            @if(auth()->user()->roles->isNotEmpty())
                                {{ auth()->user()->roles->first()->name }}
                            @else
                                No Role Assigned
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Settings -->
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h3 class="text-lg font-semibold text-white mb-4">Security Settings</h3>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-300">Two-Factor Authentication</span>
                        <button class="text-blue-400 hover:text-blue-300 text-sm">Enable</button>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-300">Login Notifications</span>
                        <button class="text-blue-400 hover:text-blue-300 text-sm">Configure</button>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-300">Active Sessions</span>
                        <button class="text-blue-400 hover:text-blue-300 text-sm">View</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
