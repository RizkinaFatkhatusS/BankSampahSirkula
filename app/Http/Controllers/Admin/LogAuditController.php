<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAudit;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Log audit: baca saja, dapat disaring. Tidak ada rute ubah atau hapus (FR-45). */
class LogAuditController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->only(['q', 'entitas', 'dari', 'sampai']);

        $log = LogAudit::with('user:id,nama,role')
            ->when($f['entitas'] ?? null, fn ($x, $v) => $x->where('entitas', $v))
            ->when($f['q'] ?? null, fn ($x, $v) => $x->where(fn ($w) => $w->where('aksi', 'like', "%{$v}%")->orWhere('alasan', 'like', "%{$v}%")->orWhereHas('user', fn ($u) => $u->where('nama', 'like', "%{$v}%"))))
            ->when($f['dari'] ?? null, fn ($x, $v) => $x->where('created_at', '>=', $v.' 00:00:00'))
            ->when($f['sampai'] ?? null, fn ($x, $v) => $x->where('created_at', '<=', $v.' 23:59:59'))
            ->orderByDesc('id')->paginate(25)->withQueryString()
            ->through(fn ($l) => [
                'id' => $l->id, 'waktu' => \Carbon\Carbon::parse($l->created_at)->translatedFormat('d M Y, H.i.s'),
                'pelaku' => $l->user?->nama ?? 'Sistem', 'role' => $l->user?->role,
                'entitas' => $l->entitas, 'entitas_id' => $l->entitas_id, 'aksi' => $l->aksi, 'alasan' => $l->alasan, 'ip' => $l->ip_address,
                'sebelum' => $l->nilai_sebelum ? json_decode($l->nilai_sebelum, true) : null,
                'sesudah' => $l->nilai_sesudah ? json_decode($l->nilai_sesudah, true) : null,
            ]);

        return Inertia::render('Admin/LogAudit', [
            'log' => $log, 'filter' => $f,
            'entitas' => LogAudit::select('entitas')->distinct()->orderBy('entitas')->pluck('entitas'),
        ]);
    }
}
