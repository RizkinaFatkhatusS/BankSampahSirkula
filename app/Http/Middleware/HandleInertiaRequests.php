<?php

namespace App\Http\Middleware;

use App\Models\MasterKategori;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'nomor_hp' => $user->nomor_hp,
                    'role' => $user->role,
                    'status_akun' => $user->status_akun,
                    'mfa_aktif' => (bool) $user->mfa_aktif,
                    // Hak akses efektif Admin (menu dan widget hanya menampilkan tugas yang diizinkan)
                    'izin' => $user->isStaf() ? $user->izinEfektif() : [],
                ] : null,
            ],
            // Supplier berstatus Dalam penyelesaian: akses terbatas sampai batas waktu
            'akunTerbatas' => $user && $user->isSupplier() && $user->status_akun === 'dalam_penyelesaian'
                ? ['batas' => $user->batas_penyelesaian?->translatedFormat('d F Y')]
                : null,
            // Data master (FR-42) untuk pilihan kategori dan satuan pada formulir Supplier
            'master' => fn () => $user && $user->isSupplier() ? [
                'kategori_sampah' => MasterKategori::daftar('kategori_sampah'),
                'kategori_olahan' => MasterKategori::daftar('kategori_olahan'),
                'satuan' => MasterKategori::daftar('satuan'),
                'label_olahan' => MasterKategori::daftar('kategori_olahan', true),
                'label_sampah' => MasterKategori::daftar('kategori_sampah', true),
            ] : null,
            'notifBelumDibaca' => fn () => $user ? $user->unreadNotifications()->count() : 0,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
