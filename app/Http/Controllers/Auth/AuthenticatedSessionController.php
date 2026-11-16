<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LogAkses;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        // Admin/Super Admin: kata sandi benar belum cukup, 2FA harus diselesaikan dulu (FR-01)
        if ($user->perluTantanganMfa()) {
            $request->session()->put('mfa', ['user_id' => $user->id, 'setup' => ! $user->mfa_aktif, 'waktu' => now()->timestamp]);

            return redirect()->route('mfa.show');
        }

        self::masuk($request, $user, $request->boolean('remember'));

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Menyelesaikan login: dipakai alur biasa dan alur 2FA. */
    public static function masuk(Request $request, User $user, bool $ingat = false, bool $mfa = false): void
    {
        // Staf tidak memakai "ingat saya"
        Auth::login($user, $user->isStaf() ? false : $ingat);
        $request->session()->regenerate();
        if ($mfa) {
            $request->session()->put('mfa_lolos', true);
        }
        $request->session()->forget('mfa');

        $user->forceFill(['terakhir_login' => now()])->save();
        LogAkses::catat('login', ['role' => $user->role], $user->id);
    }

    public function destroy(Request $request): RedirectResponse
    {
        LogAkses::catat('logout');
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
