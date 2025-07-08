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
});
