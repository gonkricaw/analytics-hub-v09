@extends('layouts.app')

@section('title', 'Analytics')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white">Analytics Dashboard</h1>
        <p class="text-gray-300 mt-2">Track system performance and user activity</p>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-sm font-medium text-gray-300 mb-2">Active Users (24h)</h3>
            <p class="text-3xl font-bold text-blue-400">{{ \App\Models\UserActivity::where('created_at', '>=', now()->subDay())->distinct('user_id')->count() }}</p>
            <p class="text-sm text-gray-400 mt-1">↑ 12% from yesterday</p>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-sm font-medium text-gray-300 mb-2">Login Attempts</h3>
            <p class="text-3xl font-bold text-green-400">{{ \App\Models\LoginAttempt::whereDate('created_at', today())->count() }}</p>
            <p class="text-sm text-gray-400 mt-1">{{ \App\Models\LoginAttempt::whereDate('created_at', today())->where('status', 'successful')->count() }} successful</p>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-sm font-medium text-gray-300 mb-2">Content Views</h3>
            <p class="text-3xl font-bold text-purple-400">0</p>
            <p class="text-sm text-gray-400 mt-1">Coming soon</p>
        </div>

        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-sm font-medium text-gray-300 mb-2">System Health</h3>
            <p class="text-3xl font-bold text-green-400">99.9%</p>
            <p class="text-sm text-gray-400 mt-1">Uptime</p>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Login Trends -->
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">Login Trends (Last 7 Days)</h3>
            <div class="space-y-2">
                @for($i = 6; $i >= 0; $i--)
                    @php
                        $date = now()->subDays($i);
                        $count = \App\Models\LoginAttempt::whereDate('created_at', $date)->where('status', 'successful')->count();
                        $width = $count > 0 ? min(100, ($count / 10) * 100) : 0;
                    @endphp
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-300">{{ $date->format('M d') }}</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-32 bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $width }}%"></div>
                            </div>
                            <span class="text-sm text-gray-400 w-8">{{ $count }}</span>
                        </div>
                    </div>
                @endfor
            </div>
        </div>

        <!-- User Activity -->
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">Recent User Activity</h3>
            <div class="space-y-3">
                @forelse(\App\Models\UserActivity::with('user')->latest()->limit(8)->get() as $activity)
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                            <span class="text-xs font-medium text-white">{{ strtoupper(substr($activity->user->name ?? 'U', 0, 1)) }}</span>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-300">
                                <span class="font-medium text-white">{{ $activity->user->name ?? 'Unknown' }}</span>
                                {{ $activity->activity_type }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-center py-4">No activity recorded</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Additional Analytics -->
    <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Failed Login Attempts -->
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">Failed Login Attempts</h3>
            <div class="space-y-3">
                @forelse(\App\Models\LoginAttempt::where('status', 'failed')->latest()->limit(5)->get() as $attempt)
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-300">{{ $attempt->email }}</p>
                            <p class="text-xs text-gray-400">{{ $attempt->ip_address }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400">{{ $attempt->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-center py-4">No failed attempts</p>
                @endforelse
            </div>
        </div>

        <!-- Top Active Users -->
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">Top Active Users</h3>
            <div class="space-y-3">
                @forelse(\App\Models\User::withCount('activities')->orderBy('activities_count', 'desc')->limit(5)->get() as $user)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 bg-gray-600 rounded-full flex items-center justify-center">
                                <span class="text-xs font-medium text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            </div>
                            <div>
                                <p class="text-sm text-gray-300">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $user->activities_count }} activities</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-center py-4">No user activity</p>
                @endforelse
            </div>
        </div>

        <!-- System Info -->
        <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4">System Information</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-300">PHP Version</span>
                    <span class="text-white">{{ phpversion() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-300">Laravel Version</span>
                    <span class="text-white">{{ app()->version() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-300">Database</span>
                    <span class="text-white">{{ config('database.default') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-300">Environment</span>
                    <span class="text-white">{{ app()->environment() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
