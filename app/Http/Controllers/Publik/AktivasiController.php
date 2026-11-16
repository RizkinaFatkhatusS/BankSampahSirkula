<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Services\AkunService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/** Tautan sekali pakai: aktivasi Supplier, undangan Admin, dan reset kata sandi (FR-37, FR-47). */
class AktivasiController extends Controller
{
    public function show(string $token)
    {
        $t = AkunService::temukan($token);

        $berlaku = $t && $t->berlaku();

        return Inertia::render('Publik/Aktivasi', [
            'token' => $token,
            'berlaku' => (bool) $berlaku,
            'jenis' => $t?->jenis,
            'nama' => $berlaku ? $t->user->nama : null,
            'alasan' => $berlaku ? null : ($t && $t->dipakai_pada ? 'Tautan ini sudah dipakai.' : 'Tautan tidak valid atau sudah kedaluwarsa.'),
            'bisaMinta' => (bool) ($t && ! $t->dipakai_pada && $t->user?->isSupplier()),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $t = AkunService::temukan($token);
        abort_unless($t && $t->berlaku(), 410, 'Tautan tidak valid atau sudah kedaluwarsa.');

        $aturan = $t->user->isStaf() ? Password::min(10)->mixedCase()->numbers() : Password::min(8);
        $request->validate(['password' => ['required', 'confirmed', $aturan]], [], ['password' => 'kata sandi']);

        AkunService::pakai($t, $request->input('password'));

        return redirect()->route('login')->with('status', 'Kata sandi berhasil ditetapkan. Silakan masuk.');
    }
}
