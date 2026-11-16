<?php

namespace App\Http\Middleware;

use App\Models\LogAkses;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hak akses Admin diperiksa pada sisi server untuk setiap rute (FR-49).
 * Pemakaian: ->middleware('izin:pengajuan.lihat') ; beberapa kode berarti "salah satu".
 */
class PerluIzin
{
    public function handle(Request $request, Closure $next, string ...$kode): Response
    {
        $user = $request->user();
        $boleh = $user && $user->isStaf() && collect($kode)->contains(fn ($k) => $user->punya($k));

        if (! $boleh) {
            LogAkses::catat('penolakan_403', ['alasan' => 'izin', 'dibutuhkan' => implode(',', $kode)]);
            abort(403, 'Anda tidak memiliki hak akses untuk tindakan ini.');
        }

        return $next($request);
    }
}
