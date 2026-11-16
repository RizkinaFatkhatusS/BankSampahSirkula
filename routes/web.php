<?php

use App\Http\Controllers\Customer;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
]))->name('home');

// /dashboard mengarahkan ke beranda sesuai peran (PRD 5.5)
Route::get('/dashboard', function () {
    $u = Auth::user();

    return redirect()->route(match (true) {
        $u->isStaf() => 'admin.beranda',
        $u->isSupplier() => 'supplier.beranda',
        default => 'customer.beranda',
    });
})->middleware('auth')->name('dashboard');

// Profil akun (dipakai kedua peran)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/* ------------------------------------------------------------------ CUSTOMER */
Route::middleware(['auth', 'role:customer'])->prefix('customer')->name('customer.')->group(function () {
    Route::get('/beranda', [Customer\BerandaController::class, 'index'])->name('beranda');

    Route::get('/bank-sampah', [Customer\BankSampahController::class, 'index'])->name('bank.index');
    Route::get('/bank-sampah/{bank}', [Customer\BankSampahController::class, 'show'])->name('bank.show');

    Route::get('/setoran/create', [Customer\SetoranController::class, 'create'])->name('setoran.create');
    Route::post('/setoran', [Customer\SetoranController::class, 'store'])->name('setoran.store');
    Route::get('/setoran/{setoran}', [Customer\SetoranController::class, 'show'])->name('setoran.show');
    Route::post('/setoran/{setoran}/batal', [Customer\SetoranController::class, 'batal'])->name('setoran.batal');

    Route::get('/riwayat', [Customer\RiwayatController::class, 'index'])->name('riwayat');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
});

/* ------------------------------------------------------------------ SUPPLIER */
Route::middleware(['auth', 'role:supplier'])->prefix('supplier')->name('supplier.')->group(function () {
    Route::get('/beranda', [Supplier\BerandaController::class, 'index'])->name('beranda');
    Route::get('/dashboard', [Supplier\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/setoran', [Supplier\SetoranController::class, 'index'])->name('setoran.index');
    Route::get('/setoran/{setoran}', [Supplier\SetoranController::class, 'show'])->name('setoran.show');
    Route::post('/setoran/{setoran}/setujui', [Supplier\SetoranController::class, 'setujui'])->name('setoran.setujui');
    Route::post('/setoran/{setoran}/terima', [Supplier\SetoranController::class, 'terima'])->name('setoran.terima');
    Route::post('/setoran/{setoran}/tolak', [Supplier\SetoranController::class, 'tolak'])->name('setoran.tolak');
    Route::post('/setoran/{setoran}/penjemputan', [Supplier\SetoranController::class, 'penjemputan'])->name('setoran.penjemputan');
    Route::post('/setoran/{setoran}/timbang', [Supplier\SetoranController::class, 'timbang'])->name('setoran.timbang');

    Route::get('/jenis-sampah', [Supplier\JenisSampahController::class, 'index'])->name('jenis.index');
    Route::post('/jenis-sampah', [Supplier\JenisSampahController::class, 'store'])->name('jenis.store');
    Route::patch('/jenis-sampah/{jenis}', [Supplier\JenisSampahController::class, 'update'])->name('jenis.update');
    Route::delete('/jenis-sampah/{jenis}', [Supplier\JenisSampahController::class, 'destroy'])->name('jenis.destroy');

    Route::get('/profil', [Supplier\ProfilController::class, 'edit'])->name('profil');
    Route::patch('/profil', [Supplier\ProfilController::class, 'update'])->name('profil.update');

    Route::get('/pencairan', [Supplier\PencairanController::class, 'index'])->name('pencairan.index');
    Route::post('/pencairan', [Supplier\PencairanController::class, 'store'])->name('pencairan.store');

    Route::get('/pengolahan', [Supplier\PengolahanController::class, 'index'])->name('pengolahan.index');
    Route::post('/pengolahan', [Supplier\PengolahanController::class, 'store'])->name('pengolahan.store');

    Route::get('/produk', [Supplier\ProdukController::class, 'index'])->name('produk.index');
    Route::patch('/produk/{produk}', [Supplier\ProdukController::class, 'update'])->name('produk.update');
    Route::delete('/produk/{produk}', [Supplier\ProdukController::class, 'destroy'])->name('produk.destroy');

    Route::get('/mitra', [Supplier\MitraController::class, 'index'])->name('mitra.index');
    Route::post('/mitra', [Supplier\MitraController::class, 'store'])->name('mitra.store');
    Route::patch('/mitra/{mitra}', [Supplier\MitraController::class, 'update'])->name('mitra.update');
    Route::delete('/mitra/{mitra}', [Supplier\MitraController::class, 'destroy'])->name('mitra.destroy');

    Route::get('/penjualan', [Supplier\PenjualanController::class, 'index'])->name('penjualan.index');
    Route::post('/penjualan', [Supplier\PenjualanController::class, 'store'])->name('penjualan.store');
    Route::patch('/penjualan/{penjualan}', [Supplier\PenjualanController::class, 'update'])->name('penjualan.update');

    Route::get('/laporan', [Supplier\LaporanController::class, 'index'])->name('laporan.index');
    Route::post('/laporan', [Supplier\LaporanController::class, 'store'])->name('laporan.store');
    Route::get('/laporan/unduh', [Supplier\LaporanController::class, 'unduh'])->name('laporan.unduh');

    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');
});

require __DIR__.'/auth.php';
require __DIR__.'/publik.php';
require __DIR__.'/admin.php';
