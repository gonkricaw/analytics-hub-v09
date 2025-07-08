<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name', 'Analytics Hub') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        :root {
            --bs-body-bg: #121212;
            --bs-body-color: #ffffff;
            --bs-border-color: #333333;
        }

        body {
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #1a1a1a;
            border-right: 1px solid #333333;
        }

        .sidebar .nav-link {
            color: #cccccc;
            padding: 0.75rem 1rem;
            margin-bottom: 0.25rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease-in-out;
        }

        .sidebar .nav-link:hover {
            background-color: #333333;
            color: #ffffff;
        }

        .sidebar .nav-link.active {
            background-color: #0d6efd;
            color: #ffffff;
        }

        .main-content {
            min-height: 100vh;
        }

        .card {
            border: 1px solid #333333;
            background-color: #1a1a1a;
        }

        .card-header {
            background-color: #1a1a1a;
            border-bottom: 1px solid #333333;
        }

        .table-dark {
            --bs-table-bg: #1a1a1a;
            --bs-table-border-color: #333333;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .btn-outline-primary:hover {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .pagination .page-link {
            background-color: #1a1a1a;
            border-color: #333333;
            color: #cccccc;
        }

        .pagination .page-link:hover {
            background-color: #333333;
            border-color: #333333;
            color: #ffffff;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .breadcrumb {
            background-color: transparent;
        }

        .breadcrumb-item a {
            color: #0d6efd;
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar p-3" style="width: 280px;">
            <div class="mb-4">
                <h5 class="text-light mb-0">
                    <i class="fas fa-chart-line me-2"></i>Analytics Hub
                </h5>
                <small class="text-muted">Administration Panel</small>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                </li>

                <!-- User Management -->
                <li class="nav-item mt-3">
                    <h6 class="text-muted text-uppercase small fw-bold">User Management</h6>
                </li>
                <li class="nav-item">
                    <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                        <i class="fas fa-users me-2"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.roles.index') }}" class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                        <i class="fas fa-user-shield me-2"></i>Roles
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.permissions.index') }}" class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                        <i class="fas fa-key me-2"></i>Permissions
                    </a>
                </li>

                <!-- Content Management -->
                <li class="nav-item mt-3">
                    <h6 class="text-muted text-uppercase small fw-bold">Content Management</h6>
                </li>
                <li class="nav-item">
                    <a href="{{ route('content.index') }}" class="nav-link {{ request()->routeIs('content.*') ? 'active' : '' }}">
                        <i class="fas fa-file-alt me-2"></i>Content
                    </a>
                </li>

                <!-- Analytics -->
                <li class="nav-item mt-3">
                    <h6 class="text-muted text-uppercase small fw-bold">Analytics</h6>
                </li>
                <li class="nav-item">
                    <a href="{{ route('analytics.index') }}" class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                        <i class="fas fa-chart-bar me-2"></i>Reports
                    </a>
                </li>

                <!-- System -->
                <li class="nav-item mt-3">
                    <h6 class="text-muted text-uppercase small fw-bold">System</h6>
                </li>
                <li class="nav-item">
                    <a href="{{ route('system.index') }}" class="nav-link {{ request()->routeIs('system.*') ? 'active' : '' }}">
                        <i class="fas fa-cogs me-2"></i>Configuration
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.terms.stats') }}" class="nav-link {{ request()->routeIs('admin.terms.*') ? 'active' : '' }}">
                        <i class="fas fa-file-contract me-2"></i>Terms & Conditions
                    </a>
                </li>
            </ul>

            <!-- User Info -->
            <div class="mt-auto pt-4 border-top border-secondary">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fas fa-user text-white"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="text-light small">{{ Auth::user()->first_name ?? 'Admin' }} {{ Auth::user()->last_name ?? 'User' }}</div>
                        <div class="text-muted small">{{ Auth::user()->email ?? 'admin@example.com' }}</div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="{{ route('users.profile') }}"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content flex-grow-1">
            <!-- Top Navigation -->
            <nav class="navbar navbar-dark bg-dark border-bottom border-secondary px-4">
                <div class="d-flex justify-content-between align-items-center w-100">
                    <div>
                        @hasSection('breadcrumb')
                            @yield('breadcrumb')
                        @endif
                    </div>
                    <div class="d-flex align-items-center">
                        <!-- Notifications -->
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-light btn-sm" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span class="badge bg-danger rounded-pill small">3</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><a class="dropdown-item" href="#">New user registration</a></li>
                                <li><a class="dropdown-item" href="#">System update available</a></li>
                                <li><a class="dropdown-item" href="#">Terms updated</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                            </ul>
                        </div>

                        <!-- Quick Actions -->
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-plus me-1"></i>Quick Actions
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                                <li><a class="dropdown-item" href="{{ route('admin.roles.create') }}"><i class="fas fa-user-shield me-2"></i>Create Role</a></li>
                                <li><a class="dropdown-item" href="{{ route('admin.permissions.create') }}"><i class="fas fa-key me-2"></i>Create Permission</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-plus me-2"></i>Add User</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-file-plus me-2"></i>Create Content</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="p-4">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Scripts -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>

    @stack('scripts')
</body>
</html>
