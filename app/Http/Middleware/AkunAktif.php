<?php

namespace App\Http\Middleware;

use App\Models\LogAkses;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Status akun diperiksa pada setiap permintaan, sehingga sesi yang sedang berjalan ikut terhenti
 * saat Admin menangguhkan/menonaktifkan akun (PRD 5.3). Juga menegakkan 2FA dan batas sesi staf.
 */
class AkunAktif
{
    /** Rute yang tetap boleh dibuka Supplier berstatus Dalam penyelesaian (FR-50). */
    private const RUTE_TERBATAS = [
        'dashboard', 'logout', 'mfa.*', 'profile.*',
        'supplier.beranda', 'supplier.setoran.index', 'supplier.setoran.show', 'supplier.setoran.timbang',
        'supplier.pencairan.*', 'supplier.laporan.*', 'supplier.notifikasi*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }
        $rute = $request->route()?->getName() ?? '';

        if ($pesan = $user->pesanStatusLogin()) {
            return $this->keluar($request, $pesan);
        }

        if ($user->isSupplier() && $user->status_akun === 'dalam_penyelesaian' && ! Str::is(self::RUTE_TERBATAS, $rute)) {
            abort(403, 'Akun Anda dalam penyelesaian. Fitur ini tidak tersedia; Anda hanya dapat menimbang setoran yang sudah diterima, mencatat pencairan saldo, dan mengunduh laporan.');
        }

        if ($user->isStaf()) {
            // Batas waktu sesi tidak aktif untuk staf
            $menit = (int) config('sirkula.sesi_staf_menit', 30);
            $terakhir = $request->session()->get('aktivitas_terakhir');
            if ($terakhir && now()->timestamp - $terakhir > $menit * 60) {
                return $this->keluar($request, 'Sesi berakhir karena tidak aktif. Silakan masuk kembali.');
            }
            $request->session()->put('aktivitas_terakhir', now()->timestamp);

            if ($user->perluTantanganMfa() && ! $request->session()->get('mfa_lolos') && ! Str::is(['mfa.*', 'logout'], $rute)) {
                if (! $user->mfa_aktif) {
                    return redirect()->route('mfa.show');   // wajib mengatur 2FA lebih dulu
                }

                return $this->keluar($request, 'Silakan masuk kembali dan selesaikan autentikasi dua langkah.');
            }
        }

        return $next($request);
    }

    private function keluar(Request $request, string $pesan): Response
    {
        LogAkses::catat('logout', ['alasan' => 'sesi dihentikan']);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => $pesan]);
    }
}
