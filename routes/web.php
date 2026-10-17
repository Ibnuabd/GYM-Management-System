<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Role-protected mock routes (for testing RBAC authorization and guards)
    Route::get('/kiosk', function () {
        return response()->json(['message' => 'Akses Kios Kehadiran diizinkan', 'role' => auth()->user()->getRoleNames()]);
    })->middleware('permission:scan_attendance')->name('kiosk');

    Route::get('/pos', function () {
        return response()->json(['message' => 'Akses Kasir POS diizinkan', 'role' => auth()->user()->getRoleNames()]);
    })->middleware('permission:pos_cashier')->name('pos');

    Route::get('/pt', function () {
        return response()->json(['message' => 'Akses Dashboard PT diizinkan', 'role' => auth()->user()->getRoleNames()]);
    })->middleware('permission:manage_pt_sessions')->name('pt.dashboard');

    Route::get('/reports', function () {
        return response()->json(['message' => 'Akses Laporan Keuangan diizinkan', 'role' => auth()->user()->getRoleNames()]);
    })->middleware('permission:view_reports')->name('reports');

    Route::get('/users', function () {
        return response()->json(['message' => 'Akses Manajemen User diizinkan', 'role' => auth()->user()->getRoleNames()]);
    })->middleware('permission:manage_users')->name('users');
});
