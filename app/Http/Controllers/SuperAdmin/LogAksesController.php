<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LogAkses;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogAksesController extends Controller
{
    public function index(Request $request)
    {
        $jenis = $request->query('jenis');
        $q = trim((string) $request->query('q', ''));

        $log = LogAkses::with('user:id,nama,role')
            ->when($jenis, fn ($x) => $x->where('jenis', $jenis))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('detail', 'like', "%{$q}%")->orWhere('ip_address', 'like', "%{$q}%")->orWhereHas('user', fn ($u) => $u->where('nama', 'like', "%{$q}%"))))
            ->orderByDesc('id')->paginate(30)->withQueryString()
            ->through(fn ($l) => [
                'id' => $l->id, 'waktu' => \Carbon\Carbon::parse($l->created_at)->translatedFormat('d M Y, H.i.s'), 'jenis' => $l->jenis,
                'pengguna' => $l->user?->nama ?? '-', 'role' => $l->user?->role, 'rute' => $l->rute, 'ip' => $l->ip_address,
                'detail' => $l->detail ? json_decode($l->detail, true) : null,
            ]);

        return Inertia::render('SuperAdmin/LogAkses', [
            'log' => $log, 'filter' => ['jenis' => $jenis, 'q' => $q],
            'jenisPilihan' => ['login', 'login_gagal', 'logout', 'penolakan_403', 'buka_dokumen', 'perubahan_role', 'mfa'],
        ]);
    }
}
