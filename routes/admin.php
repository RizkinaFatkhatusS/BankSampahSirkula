<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\SuperAdmin;
use Illuminate\Support\Facades\Route;

/* ------------------------------------------------------------------ ADMIN & SUPER ADMIN
 * Tiap rute memeriksa hak akses di sisi server (middleware izin:), bukan hanya menyembunyikan menu. */
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/beranda', [Admin\BerandaController::class, 'index'])->name('beranda');

    // Notifikasi (tanpa hak akses khusus)
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::post('/notifikasi/{id}/baca', [NotifikasiController::class, 'baca'])->name('notifikasi.baca');

    // Pendaftaran Supplier
    Route::get('/pengajuan', [Admin\PengajuanController::class, 'index'])->middleware('izin:pengajuan.lihat')->name('pengajuan.index');
    Route::get('/pengajuan/create', [Admin\PengajuanController::class, 'create'])->middleware('izin:pengajuan.buat')->name('pengajuan.create');
    Route::post('/pengajuan', [Admin\PengajuanController::class, 'store'])->middleware('izin:pengajuan.buat')->name('pengajuan.store');
    Route::get('/pengajuan/{pengajuan}', [Admin\PengajuanController::class, 'show'])->middleware('izin:pengajuan.lihat')->name('pengajuan.show');
    Route::get('/pengajuan/{pengajuan}/dokumen/{dokumen}', [Admin\PengajuanController::class, 'dokumen'])->middleware('izin:pengajuan.dokumen')->name('pengajuan.dokumen');
    Route::post('/pengajuan/{pengajuan}/mulai', [Admin\PengajuanController::class, 'mulai'])->middleware('izin:pengajuan.putuskan')->name('pengajuan.mulai');
    Route::post('/pengajuan/{pengajuan}/dokumen/{dokumen}/periksa', [Admin\PengajuanController::class, 'periksa'])->middleware('izin:pengajuan.putuskan')->name('pengajuan.periksa');
    Route::post('/pengajuan/{pengajuan}/catatan', [Admin\PengajuanController::class, 'catatan'])->middleware('izin:pengajuan.putuskan')->name('pengajuan.catatan');
    Route::post('/pengajuan/{pengajuan}/setujui', [Admin\PengajuanController::class, 'setujui'])->middleware('izin:pengajuan.putuskan')->name('pengajuan.setujui');
    Route::post('/pengajuan/{pengajuan}/revisi', [Admin\PengajuanController::class, 'revisi'])->middleware('izin:pengajuan.putuskan')->name('pengajuan.revisi');
    Route::post('/pengajuan/{pengajuan}/tolak', [Admin\PengajuanController::class, 'tolak'])->middleware('izin:pengajuan.putuskan')->name('pengajuan.tolak');
    Route::post('/pengajuan/{pengajuan}/alihkan', [Admin\PengajuanController::class, 'alihkan'])->middleware('izin:pengajuan.alihkan')->name('pengajuan.alihkan');

    // Akun Supplier
    Route::get('/supplier', [Admin\SupplierController::class, 'index'])->middleware('izin:supplier.lihat')->name('supplier.index');
    Route::get('/supplier/{supplier}', [Admin\SupplierController::class, 'show'])->middleware('izin:supplier.lihat')->name('supplier.show');
    Route::post('/supplier/{supplier}/status', [Admin\SupplierController::class, 'status'])->middleware('izin:supplier.status')->name('supplier.status');
    Route::post('/supplier/{supplier}/nonaktifkan', [Admin\SupplierController::class, 'nonaktifkan'])->middleware('izin:supplier.nonaktifkan')->name('supplier.nonaktifkan');
    Route::post('/supplier/{supplier}/aktivasi-ulang', [Admin\SupplierController::class, 'aktivasiUlang'])->middleware('izin:supplier.akses')->name('supplier.aktivasi-ulang');
    Route::post('/supplier/{supplier}/reset-password', [Admin\SupplierController::class, 'resetPassword'])->middleware('izin:supplier.akses')->name('supplier.reset-password');

    // Akun Customer
    Route::get('/customer', [Admin\CustomerController::class, 'index'])->middleware('izin:customer.lihat')->name('customer.index');
    Route::post('/customer/{customer}/status', [Admin\CustomerController::class, 'status'])->middleware('izin:customer.status')->name('customer.status');
    Route::post('/customer/{customer}/reset-password', [Admin\CustomerController::class, 'resetPassword'])->middleware('izin:customer.status')->name('customer.reset-password');

    // Bank sampah, saldo belum dicairkan
    Route::get('/bank-sampah', [Admin\BankSampahController::class, 'index'])->middleware('izin:banksampah.lihat')->name('bank.index');
    Route::patch('/bank-sampah/{bank}', [Admin\BankSampahController::class, 'update'])->middleware('izin:banksampah.kelola')->name('bank.update');
    Route::get('/saldo-belum-dicairkan', [Admin\SaldoController::class, 'index'])->middleware('izin:banksampah.saldo')->name('saldo.index');

    // Data master
    Route::get('/master', [Admin\MasterController::class, 'index'])->middleware('izin:master.lihat')->name('master.index');
    Route::post('/master', [Admin\MasterController::class, 'store'])->middleware('izin:master.kelola')->name('master.store');
    Route::patch('/master/{grup}/{id}', [Admin\MasterController::class, 'update'])->middleware('izin:master.kelola')->name('master.update');

    // Monitoring dan laporan
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->middleware('izin:dashboard.lihat')->name('dashboard');
    Route::get('/laporan', [Admin\LaporanController::class, 'index'])->middleware('izin:laporan.unduh')->name('laporan.index');
    Route::get('/laporan/unduh', [Admin\LaporanController::class, 'unduh'])->middleware('izin:laporan.unduh')->name('laporan.unduh');

    // Audit dan email
    Route::get('/log-audit', [Admin\LogAuditController::class, 'index'])->middleware('izin:audit.lihat')->name('log-audit');
    Route::get('/log-email', [Admin\LogEmailController::class, 'index'])->middleware('izin:email.kelola')->name('log-email');
    Route::post('/log-email/{log}/kirim-ulang', [Admin\LogEmailController::class, 'kirimUlang'])->middleware('izin:email.kelola')->name('log-email.kirim-ulang');
});

Route::middleware(['auth', 'role:super_admin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/admin', [SuperAdmin\AdminController::class, 'index'])->middleware('izin:admin.kelola')->name('admin.index');
    Route::get('/admin/create', [SuperAdmin\AdminController::class, 'create'])->middleware('izin:admin.kelola')->name('admin.create');
    Route::post('/admin', [SuperAdmin\AdminController::class, 'store'])->middleware('izin:admin.kelola')->name('admin.store');
    Route::get('/admin/{admin}/edit', [SuperAdmin\AdminController::class, 'edit'])->middleware('izin:admin.kelola')->name('admin.edit');
    Route::patch('/admin/{admin}', [SuperAdmin\AdminController::class, 'update'])->middleware('izin:admin.kelola')->name('admin.update');
    Route::post('/admin/{admin}/status', [SuperAdmin\AdminController::class, 'status'])->middleware('izin:admin.kelola')->name('admin.status');
    Route::post('/admin/{admin}/undang-ulang', [SuperAdmin\AdminController::class, 'undangUlang'])->middleware('izin:admin.kelola')->name('admin.undang-ulang');
    Route::post('/admin/{admin}/reset-password', [SuperAdmin\AdminController::class, 'resetPassword'])->middleware('izin:admin.kelola')->name('admin.reset-password');

    Route::get('/role', [SuperAdmin\RoleController::class, 'index'])->middleware('izin:role.kelola')->name('role.index');
    Route::get('/role/create', [SuperAdmin\RoleController::class, 'create'])->middleware('izin:role.kelola')->name('role.create');
    Route::post('/role', [SuperAdmin\RoleController::class, 'store'])->middleware('izin:role.kelola')->name('role.store');
    Route::get('/role/{role}/edit', [SuperAdmin\RoleController::class, 'edit'])->middleware('izin:role.kelola')->name('role.edit');
    Route::patch('/role/{role}', [SuperAdmin\RoleController::class, 'update'])->middleware('izin:role.kelola')->name('role.update');
    Route::delete('/role/{role}', [SuperAdmin\RoleController::class, 'destroy'])->middleware('izin:role.kelola')->name('role.destroy');
    Route::post('/role/{role}/duplikat', [SuperAdmin\RoleController::class, 'duplikat'])->middleware('izin:role.kelola')->name('role.duplikat');

    Route::get('/pengaturan', [SuperAdmin\PengaturanController::class, 'index'])->middleware('izin:sistem.atur')->name('pengaturan');
    Route::patch('/pengaturan', [SuperAdmin\PengaturanController::class, 'update'])->middleware('izin:sistem.atur')->name('pengaturan.update');
    Route::get('/log-akses', [SuperAdmin\LogAksesController::class, 'index'])->middleware('izin:akses.log')->name('log-akses');
});
