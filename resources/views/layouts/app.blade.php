<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Analytics Hub') }} - @yield('title')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Additional CSS -->
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-900 text-white">
    <div class="min-h-screen">
        @auth
            <!-- Navigation -->
            <nav class="bg-gray-800 border-b border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <h1 class="text-xl font-bold text-white">Analytics Hub</h1>
                            </div>
                            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                                <a href="{{ route('dashboard') }}" class="border-transparent text-gray-300 hover:text-white hover:border-gray-300 border-b-2 px-1 pt-1 pb-1 text-sm font-medium">
                                    Dashboard
                                </a>
                                <a href="{{ route('users.index') }}" class="border-transparent text-gray-300 hover:text-white hover:border-gray-300 border-b-2 px-1 pt-1 pb-1 text-sm font-medium">
                                    Users
                                </a>
                                <a href="{{ route('content.index') }}" class="border-transparent text-gray-300 hover:text-white hover:border-gray-300 border-b-2 px-1 pt-1 pb-1 text-sm font-medium">
                                    Content
                                </a>
                                <a href="{{ route('analytics.index') }}" class="border-transparent text-gray-300 hover:text-white hover:border-gray-300 border-b-2 px-1 pt-1 pb-1 text-sm font-medium">
                                    Analytics
                                </a>
                                <a href="{{ route('system.index') }}" class="border-transparent text-gray-300 hover:text-white hover:border-gray-300 border-b-2 px-1 pt-1 pb-1 text-sm font-medium">
                                    System
                                </a>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <div class="text-sm text-gray-300">
                                Welcome, {{ auth()->user()->full_name }}
                            </div>
                            <a href="{{ route('users.profile') }}" class="text-gray-300 hover:text-white">
                                Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-300 hover:text-white">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </nav>
        @endauth

        <!-- Page Content -->
        <main class="@auth py-8 @endauth">
            @yield('content')
        </main>
    </div>

    <!-- Additional Scripts -->
    @stack('scripts')
</body>
</html>
