<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogEmail;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LogEmailController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $log = LogEmail::query()
            ->when($status, fn ($x) => $x->where('status', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('penerima', 'like', "%{$q}%")->orWhere('jenis', 'like', "%{$q}%")))
            ->orderByDesc('id')->paginate(25)->withQueryString()
            ->through(fn ($l) => [
                'id' => $l->id, 'jenis' => $l->jenis, 'penerima' => $l->penerima, 'status' => $l->status, 'percobaan' => $l->percobaan,
                'error' => $l->pesan_error, 'subjek' => $l->muatan['subjek'] ?? '', 'waktu' => $l->created_at->translatedFormat('d M Y, H.i'),
                'dikirim' => $l->dikirim_pada?->translatedFormat('d M Y, H.i'),
            ]);

        return Inertia::render('Admin/LogEmail', [
            'log' => $log, 'filter' => ['status' => $status, 'q' => $q],
            'hitung' => LogEmail::selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status'),
        ]);
    }

    public function kirimUlang(LogEmail $log)
    {
        EmailService::kirimUlang($log);

        return back()->with('success', 'Email dijadwalkan untuk dikirim ulang.');
    }
}
