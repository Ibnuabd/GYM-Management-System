<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AttendanceKioskController;
use App\Http\Controllers\AttendanceLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPortalAuthController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\MemberPortalPasswordController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PersonalTrainerController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated Staff Routes
// "user.active": staf yang dinonaktifkan admin langsung dikeluarkan (issue #27)
Route::middleware(['auth:web', 'user.active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Members Management
    Route::middleware('permission:manage_members')->group(function () {
        Route::get('/members/quick-search', [MemberController::class, 'quickSearch'])->name('members.quick-search');
        Route::get('/members/check-rfid', [MemberController::class, 'checkRfid'])->name('members.check-rfid');
        Route::get('/members', [MemberController::class, 'index'])->name('members.index');
        Route::get('/members/create', [MemberController::class, 'create'])->name('members.create');
        Route::post('/members', [MemberController::class, 'store'])->name('members.store');
        Route::get('/members/{member}', [MemberController::class, 'show'])->name('members.show');
        Route::get('/members/{member}/edit', [MemberController::class, 'edit'])->name('members.edit');
        Route::put('/members/{member}', [MemberController::class, 'update'])->name('members.update');
        Route::delete('/members/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

        // Staf mengatur / mereset password portal member
        Route::put('/members/{member}/portal-password', [MemberPortalPasswordController::class, 'update'])->name('members.portal-password.update');

        // Packages: CS boleh MELIHAT daftar paket
        Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    });

    // Packages: hanya admin & supervisor yang boleh mengubah paket dan harganya
    Route::middleware('permission:manage_packages')->group(function () {
        Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
        Route::put('/packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
    });

    // Subscriptions Management
    Route::middleware('permission:manage_subscriptions')->group(function () {
        Route::post('/members/{member}/subscriptions', [SubscriptionController::class, 'store'])->name('subscriptions.store');
        Route::post('/subscriptions/{subscription}/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
        Route::post('/subscriptions/{subscription}/activate', [SubscriptionController::class, 'activate'])->name('subscriptions.activate');
    });

    // Attendance Kiosk
    Route::middleware('permission:scan_attendance')->group(function () {
        Route::get('/kiosk', [AttendanceKioskController::class, 'index'])->name('kiosk');
        Route::post('/kiosk/scan', [AttendanceKioskController::class, 'scan'])->name('kiosk.scan');
        Route::get('/kiosk/recent', [AttendanceKioskController::class, 'recentLogs'])->name('kiosk.recent');
    });

    // POS Retail Cashier & Transactions
    Route::middleware('permission:pos_cashier')->group(function () {
        Route::get('/pos', [PosController::class, 'index'])->name('pos');
        Route::get('/pos/search', [PosController::class, 'search'])->name('pos.search');
        Route::post('/pos/checkout', [PosController::class, 'checkout'])->name('pos.checkout');
        Route::post('/pos/transactions/{transaction}/void', [PosController::class, 'voidTransaction'])->name('pos.void');
        Route::delete('/pos/transactions/{transaction}', [PosController::class, 'destroy'])->name('pos.destroy');

        // Product Catalog: kasir boleh MELIHAT katalog produk
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    });

    // Product Catalog: hanya admin & supervisor yang boleh mengubah produk, harga, dan stok
    Route::middleware('permission:manage_products')->group(function () {
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    });

    // Personal Trainer Operations
    Route::middleware('permission:manage_pt_sessions')->group(function () {
        Route::get('/pt', [PersonalTrainerController::class, 'index'])->name('pt.dashboard');
        Route::post('/pt/sessions', [PersonalTrainerController::class, 'storeSession'])->name('pt.sessions.store');
        Route::get('/pt/members/{member}/history', [PersonalTrainerController::class, 'memberHistory'])->name('pt.members.history');
    });

    // Financial & Operational Reports
    Route::middleware('permission:view_reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
        Route::get('/reports/export/excel', [ReportController::class, 'exportExcel'])->name('reports.export.excel');

        // Log Kehadiran & Foto (riwayat scan RFID + snapshot webcam)
        Route::get('/attendances', [AttendanceLogController::class, 'index'])->name('attendances.index');
    });

    // Manajemen User (RBAC): hanya admin. Tidak ada route hapus, user cukup dinonaktifkan.
    Route::middleware('permission:manage_users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password.update');
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });

    // Log Aktivitas semua staf: hanya admin. Hanya bisa DILIHAT (tidak ada route ubah / hapus).
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])
        ->middleware('permission:view_activity_logs')
        ->name('activity-logs.index');
});

// Member Self-Service Portal Guest Routes
Route::prefix('portal')->group(function () {
    Route::get('/login', [MemberPortalAuthController::class, 'showLogin'])->name('portal.login');
    Route::post('/login', [MemberPortalAuthController::class, 'login']);
});

// Member Self-Service Portal Authenticated Routes (Strict Read-Only)
Route::middleware('auth:member')->prefix('portal')->group(function () {
    // Logout tetap boleh walaupun status member sudah tidak aktif.
    Route::post('/logout', [MemberPortalAuthController::class, 'logout'])->name('portal.logout');
});

// Halaman portal: hanya untuk member yang statusnya "active"
Route::middleware(['auth:member', 'member.active'])->prefix('portal')->group(function () {
    Route::get('/', [MemberPortalController::class, 'index'])->name('portal.dashboard');
    Route::get('/card', [MemberPortalController::class, 'digitalCard'])->name('portal.card');
    Route::get('/pt-progress', [MemberPortalController::class, 'ptProgress'])->name('portal.pt-progress');
    Route::get('/workouts', [MemberPortalController::class, 'workoutHistory'])->name('portal.workouts');
});
