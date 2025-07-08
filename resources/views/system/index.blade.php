@extends('layouts.app')

@section('title', 'System Configuration')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">System Configuration</h1>
        <p class="text-gray-300 mt-2">Manage system settings and configurations</p>
    </div>

    <!-- Configuration Tabs -->
    <div class="bg-gray-800 rounded-lg border border-gray-700">
        <div class="border-b border-gray-700">
            <nav class="flex space-x-8 px-6">
                <a href="#" class="border-b-2 border-blue-500 text-blue-400 py-4 px-1 text-sm font-medium">
                    General Settings
                </a>
                <a href="#" class="border-b-2 border-transparent text-gray-400 hover:text-white py-4 px-1 text-sm font-medium">
                    Security
                </a>
                <a href="#" class="border-b-2 border-transparent text-gray-400 hover:text-white py-4 px-1 text-sm font-medium">
                    Email
                </a>
                <a href="#" class="border-b-2 border-transparent text-gray-400 hover:text-white py-4 px-1 text-sm font-medium">
                    Backup
                </a>
                <a href="#" class="border-b-2 border-transparent text-gray-400 hover:text-white py-4 px-1 text-sm font-medium">
                    Logs
                </a>
            </nav>
        </div>

        <!-- General Settings -->
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- System Information -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">System Information</h3>
                        <div class="bg-gray-700 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-300">Application Name</span>
                                <span class="text-white">{{ config('app.name') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-300">Version</span>
                                <span class="text-white">1.0.0</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-300">Environment</span>
                                <span class="text-white">{{ app()->environment() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-300">Debug Mode</span>
                                <span class="text-white">{{ config('app.debug') ? 'Enabled' : 'Disabled' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-300">Timezone</span>
                                <span class="text-white">{{ config('app.timezone') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Database Information -->
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">Database Information</h3>
                        <div class="bg-gray-700 rounded-lg p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-300">Connection</span>
                                <span class="text-white">{{ config('database.default') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-300">Database</span>
                                <span class="text-white">{{ config('database.connections.'.config('database.default').'.database') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-300">Tables</span>
                                <span class="text-white">{{ \DB::select('SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = ?', [config('database.connections.'.config('database.default').'.database')])[0]->count }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-300">Status</span>
                                <span class="text-green-400">✓ Connected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Settings -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-semibold text-white mb-4">Configuration</h3>
                        <form class="space-y-4">
                            @csrf
                            <div>
                                <label for="app_name" class="block text-sm font-medium text-gray-300">Application Name</label>
                                <input type="text" id="app_name" name="app_name" value="{{ config('app.name') }}"
                                       class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="app_url" class="block text-sm font-medium text-gray-300">Application URL</label>
                                <input type="url" id="app_url" name="app_url" value="{{ config('app.url') }}"
                                       class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="session_timeout" class="block text-sm font-medium text-gray-300">Session Timeout (minutes)</label>
                                <input type="number" id="session_timeout" name="session_timeout" value="30"
                                       class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="max_login_attempts" class="block text-sm font-medium text-gray-300">Max Login Attempts</label>
                                <input type="number" id="max_login_attempts" name="max_login_attempts" value="30"
                                       class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label for="password_expiry" class="block text-sm font-medium text-gray-300">Password Expiry (days)</label>
                                <input type="number" id="password_expiry" name="password_expiry" value="90"
                                       class="mt-1 block w-full bg-gray-700 border border-gray-600 rounded-md px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" id="maintenance_mode" name="maintenance_mode"
                                       class="rounded border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500">
                                <label for="maintenance_mode" class="ml-2 block text-sm text-gray-300">
                                    Maintenance Mode
                                </label>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                    Save Configuration
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">System Health</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-300">Web Server</span>
                    <span class="text-green-400">✓ Running</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-300">Database</span>
                    <span class="text-green-400">✓ Connected</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-300">Cache</span>
                    <span class="text-green-400">✓ Active</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-300">Queue</span>
                    <span class="text-green-400">✓ Processing</span>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">Recent Actions</h3>
            <div class="space-y-3">
                <div class="text-sm">
                    <p class="text-gray-300">System backup completed</p>
                    <p class="text-gray-400">2 hours ago</p>
                </div>
                <div class="text-sm">
                    <p class="text-gray-300">Cache cleared</p>
                    <p class="text-gray-400">5 hours ago</p>
                </div>
                <div class="text-sm">
                    <p class="text-gray-300">User permissions updated</p>
                    <p class="text-gray-400">1 day ago</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">Quick Actions</h3>
            <div class="space-y-3">
                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md">
                    Clear Cache
                </button>
                <button class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-md">
                    Run Backup
                </button>
                <button class="w-full bg-yellow-600 hover:bg-yellow-700 text-white py-2 rounded-md">
                    View Logs
                </button>
                <button class="w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-md">
                    Maintenance Mode
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
