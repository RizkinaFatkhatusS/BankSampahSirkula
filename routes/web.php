<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ===== TAMBAHKAN INI DI BAGIAN ATAS (Import Controller Customer & Supplier) =====
use App\Http\Controllers\Customer\DashboardController as CustomerDashboard;
use App\Http\Controllers\Supplier\DashboardController as SupplierDashboard;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// ===== UBAH BAGIAN INI (Redirect otomatis /dashboard berdasarkan role) =====
Route::get('/dashboard', function () {
    $user = auth()->user();
    
    if ($user->role === 'supplier') {
        return redirect()->route('supplier.beranda');
    }
    
    return redirect()->route('customer.beranda');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== TAMBAHKAN INI DI BAGIAN BAWAH (Rute khusus RBAC Customer & Supplier) =====
// Rute khusus Customer (harus login dan memiliki role customer)
Route::middleware(['auth', 'verified', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/beranda', [CustomerDashboard::class, 'index'])->name('beranda');
});

// Rute khusus Supplier / Petugas (harus login dan memiliki role supplier)
Route::middleware(['auth', 'verified', 'role:supplier'])->prefix('supplier')->name('supplier.')->group(function () {
    Route::get('/beranda', [SupplierDashboard::class, 'index'])->name('beranda');
});

require __DIR__.'/auth.php';