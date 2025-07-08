<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password reset routes
Route::get('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
Route::post('/forgot-password', [App\Http\Controllers\Auth\PasswordResetController::class, 'sendResetLink'])->name('password.email');
Route::get('/password/reset/{token}', [App\Http\Controllers\Auth\PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [App\Http\Controllers\Auth\PasswordResetController::class, 'resetPassword'])->name('password.update');

// First login and password expiry routes
Route::get('/first-login', [App\Http\Controllers\Auth\FirstLoginController::class, 'showFirstLoginForm'])->name('auth.first-login');
Route::post('/first-login', [App\Http\Controllers\Auth\FirstLoginController::class, 'completeFirstLogin'])->name('auth.first-login.complete');
Route::get('/password-expired', [App\Http\Controllers\Auth\FirstLoginController::class, 'showPasswordExpiredForm'])->name('auth.password-expired');
Route::post('/password-expired', [App\Http\Controllers\Auth\FirstLoginController::class, 'changeExpiredPassword'])->name('auth.password-expired.change');

// Terms and Conditions routes
Route::get('/terms', [App\Http\Controllers\Auth\TermsController::class, 'showTerms'])->name('terms.show');
Route::post('/terms/accept', [App\Http\Controllers\Auth\TermsController::class, 'acceptTerms'])->name('terms.accept');
Route::get('/terms/content', [App\Http\Controllers\Auth\TermsController::class, 'getTermsContent'])->name('terms.content');
Route::get('/terms/status', [App\Http\Controllers\Auth\TermsController::class, 'checkTermsStatus'])->name('terms.status');

// Administrative terms management routes
Route::prefix('admin/terms')->middleware(['auth.custom'])->name('admin.terms.')->group(function () {
    Route::get('/stats', [App\Http\Controllers\Auth\TermsController::class, 'getAcceptanceStats'])->name('stats');
    Route::get('/users-pending', [App\Http\Controllers\Auth\TermsController::class, 'getUsersNeedingAcceptance'])->name('users.pending');
});

// Protected routes group
Route::middleware(['auth.custom'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // User management routes
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', function () {
            return view('users.index');
        })->name('index');

        Route::get('/profile', function () {
            return view('users.profile');
        })->name('profile');
    });

    // Content management routes
    Route::prefix('content')->name('content.')->group(function () {
        Route::get('/', function () {
            return view('content.index');
        })->name('index');
    });

    // Analytics routes
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', function () {
            return view('analytics.index');
        })->name('index');
    });

    // System configuration routes
    Route::prefix('system')->name('system.')->group(function () {
        Route::get('/', function () {
            return view('system.index');
        })->name('index');
    });

    // Admin routes - Role & Permission Management
    Route::prefix('admin')->name('admin.')->middleware(['auth.custom'])->group(function () {
        // Role management routes
        Route::resource('roles', App\Http\Controllers\Admin\RoleController::class);
        Route::post('roles/{role}/toggle-status', [App\Http\Controllers\Admin\RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
        Route::post('roles/{role}/assign-permissions', [App\Http\Controllers\Admin\RoleController::class, 'assignPermissions'])->name('roles.assign-permissions');
        Route::post('roles/{role}/assign-users', [App\Http\Controllers\Admin\RoleController::class, 'assignUsers'])->name('roles.assign-users');
        Route::delete('roles/{role}/remove-user/{user}', [App\Http\Controllers\Admin\RoleController::class, 'removeUser'])->name('roles.remove-user');

        // Permission management routes
        Route::resource('permissions', App\Http\Controllers\Admin\PermissionController::class);
        Route::post('permissions/{permission}/toggle-status', [App\Http\Controllers\Admin\PermissionController::class, 'toggleStatus'])->name('permissions.toggle-status');
    });
});
