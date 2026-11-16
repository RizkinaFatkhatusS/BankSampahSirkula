<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanSupplier;
use App\Models\User;
use App\Services\LaporanPlatformService;
use App\Services\PenghentianService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Beranda Admin: widget hanya untuk tugas yang diizinkan role-nya (FR-49). */
class BerandaController extends Controller
{
    public function index(Request $request, LaporanPlatformService $laporan, PenghentianService $henti)
    {
        $u = $request->user();
        $props = ['punyaIzin' => count($u->izinEfektif()) > 0 || $u->isSuperAdmin()];

        if ($u->punya('pengajuan.lihat')) {
            $props['antrean'] = [
                'menunggu' => PengajuanSupplier::where('status', 'menunggu_verifikasi')->count(),
                'diverifikasi' => PengajuanSupplier::where('status', 'sedang_diverifikasi')->count(),
                'saya' => PengajuanSupplier::where('status', 'sedang_diverifikasi')->where('ditangani_oleh', $u->id)->count(),
                'daftar' => PengajuanSupplier::whereIn('status', ['menunggu_verifikasi', 'sedang_diverifikasi'])->orderBy('created_at')->limit(6)->get()
                    ->map(fn ($p) => ['id' => $p->id, 'kode' => $p->kode_pengajuan, 'bank' => $p->nama_bank_sampah, 'status' => $p->status, 'waktu' => $p->created_at->diffForHumans()]),
            ];
        }
        if ($u->punya('supplier.lihat')) {
            $props['akunSupplier'] = User::where('role', User::ROLE_SUPPLIER)->selectRaw('status_akun, count(*) as jumlah')->groupBy('status_akun')->pluck('jumlah', 'status_akun');
        }
        if ($u->punya('customer.lihat')) {
            $props['jumlahCustomer'] = User::where('role', User::ROLE_CUSTOMER)->count();
        }
        if ($u->punya('dashboard.lihat')) {
            $d = $laporan->dashboard();
            $props['ringkas'] = ['bank' => $d['jumlahBank'], 'berat' => $d['totalBerat'], 'nilai' => $d['totalNilai'], 'penjualan' => $d['penjualanNilai']];
        }
        if ($u->punya('banksampah.saldo')) {
            $props['saldoBelumDicairkan'] = User::where('role', User::ROLE_SUPPLIER)->whereIn('status_akun', ['ditangguhkan', 'dalam_penyelesaian', 'nonaktif'])->whereNotNull('bank_sampah_id')->get()
                ->sum(fn ($s) => $henti->saldoBelumDicairkan($s->bank_sampah_id));
        }
        $props['notifikasi'] = $u->notifications()->limit(6)->get()->map(fn ($n) => [
            'id' => $n->id, 'judul' => $n->data['judul'] ?? '', 'pesan' => $n->data['pesan'] ?? '', 'url' => $n->data['url'] ?? null, 'dibaca' => $n->read_at !== null, 'waktu' => $n->created_at->diffForHumans(),
        ]);

        return Inertia::render('Admin/Beranda', $props);
    }
}
