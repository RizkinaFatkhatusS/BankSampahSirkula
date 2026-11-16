<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LogAkses;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Totp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Autentikasi dua langkah (TOTP) untuk Admin dan Super Admin. Tahap pengaturan pertama dan tantangan kode. */
class MfaController extends Controller
{
    /** Pengguna yang sedang menyelesaikan 2FA: sebelum login (sesi mfa) atau sudah login tetapi belum mengatur 2FA. */
    private function konteks(Request $request): ?array
    {
        $mfa = $request->session()->get('mfa');
        if ($mfa && now()->timestamp - ($mfa['waktu'] ?? 0) < 600) {
            $user = User::find($mfa['user_id']);

            return $user ? [$user, false] : null;
        }

        $user = $request->user();
        if ($user && $user->isStaf() && ! $user->mfa_aktif) {
            return [$user, true];
        }

        return null;
    }

    public function show(Request $request)
    {
        $k = $this->konteks($request);
        if (! $k) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi berakhir. Silakan masuk kembali.']);
        }
        [$user] = $k;
        $setup = ! $user->mfa_aktif;

        $props = ['setup' => $setup, 'nama' => $user->nama];
        if ($setup) {
            $rahasia = $request->session()->get('mfa_rahasia') ?? Totp::buatRahasia();
            $request->session()->put('mfa_rahasia', $rahasia);
            $props['rahasia'] = $rahasia;
            $props['uri'] = Totp::uri($user->email, config('sirkula.mfa_penerbit'), $rahasia);
        }

        return Inertia::render('Auth/Mfa', $props);
    }

    public function verifikasi(Request $request)
    {
        $k = $this->konteks($request);
        if (! $k) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi berakhir. Silakan masuk kembali.']);
        }
        [$user, $sudahLogin] = $k;
        $request->validate(['kode' => ['required', 'string']], [], ['kode' => 'kode verifikasi']);

        $kunci = 'mfa:'.$user->id.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($kunci, 5)) {
            throw ValidationException::withMessages(['kode' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik.']);
        }

        $setup = ! $user->mfa_aktif;
        $rahasia = $setup ? $request->session()->get('mfa_rahasia') : $user->mfa_secret;

        if (! $rahasia || ! Totp::verifikasi($rahasia, $request->input('kode'))) {
            RateLimiter::hit($kunci, 60);
            LogAkses::catat('mfa', ['hasil' => 'gagal'], $user->id);

            throw ValidationException::withMessages(['kode' => 'Kode verifikasi salah atau sudah kedaluwarsa.']);
        }
        RateLimiter::clear($kunci);

        if ($setup) {
            $user->forceFill(['mfa_secret' => $rahasia, 'mfa_aktif' => true])->save();
            $request->session()->forget('mfa_rahasia');
            AuditLogger::catat('users', $user->id, 'mfa_diaktifkan');
        }
        LogAkses::catat('mfa', ['hasil' => 'berhasil'], $user->id);

        if ($sudahLogin) {
            $request->session()->put('mfa_lolos', true);
        } else {
            AuthenticatedSessionController::masuk($request, $user, false, true);
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
