<?php

use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Publik;
use Illuminate\Support\Facades\Route;

/* Halaman publik: pendaftaran Supplier, Cek Status Pengajuan, aktivasi akun, dan 2FA.
 * Dibatasi rate limiting agar tidak mudah di-spam atau ditebak (PRD 5.6). */

Route::get('/daftar-supplier', [Publik\PendaftaranSupplierController::class, 'create'])->name('pengajuan.daftar');
Route::post('/daftar-supplier', [Publik\PendaftaranSupplierController::class, 'store'])->middleware('throttle:5,1')->name('pengajuan.store');
Route::get('/daftar-supplier/surat-pernyataan', [Publik\PendaftaranSupplierController::class, 'suratPernyataan'])->name('pengajuan.surat');

Route::get('/daftar-supplier/status', [Publik\StatusPengajuanController::class, 'form'])->name('pengajuan.cek');
Route::post('/daftar-supplier/status', [Publik\StatusPengajuanController::class, 'cari'])->middleware('throttle:8,1')->name('pengajuan.cari');

Route::get('/status-pengajuan/{token}', [Publik\StatusPengajuanController::class, 'show'])->middleware('throttle:60,1')->name('pengajuan.status');
Route::post('/status-pengajuan/{token}', [Publik\StatusPengajuanController::class, 'kirimUlang'])->middleware('throttle:5,1')->name('pengajuan.kirim-ulang');
Route::post('/status-pengajuan/{token}/aktivasi-ulang', [Publik\StatusPengajuanController::class, 'aktivasiUlang'])->middleware('throttle:3,10')->name('pengajuan.aktivasi-ulang');
Route::get('/status-pengajuan/{token}/dokumen/{dokumen}', [Publik\StatusPengajuanController::class, 'dokumen'])->middleware('throttle:60,1')->name('pengajuan.dokumen');

Route::get('/aktivasi/{token}', [Publik\AktivasiController::class, 'show'])->middleware('throttle:30,1')->name('aktivasi');
Route::post('/aktivasi/{token}', [Publik\AktivasiController::class, 'store'])->middleware('throttle:10,1')->name('aktivasi.store');

// Autentikasi dua langkah (sebelum login penuh maupun saat wajib mengatur 2FA)
Route::get('/2fa', [MfaController::class, 'show'])->name('mfa.show');
Route::post('/2fa', [MfaController::class, 'verifikasi'])->middleware('throttle:10,1')->name('mfa.verifikasi');
