<?php

namespace App\Http\Middleware;

use App\Models\LogAkses;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatasan akses berbasis role (PRD 3.2 / NFR-02).
 * Pemakaian: ->middleware('role:customer'), 'role:supplier', atau 'role:admin,super_admin'
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            LogAkses::catat('penolakan_403', ['alasan' => 'role', 'dibutuhkan' => implode(',', $roles)]);
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
