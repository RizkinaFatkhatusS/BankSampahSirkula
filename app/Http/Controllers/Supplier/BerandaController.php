<?php

namespace App\Http\Controllers\Supplier;

use App\Models\Setoran;
use Inertia\Inertia;

class BerandaController extends BaseController
{
    public function index()
    {
        $bankId = $this->bankId();
        $q = fn () => Setoran::where('bank_sampah_id', $bankId);

        $masuk = $q()->with(['customer:id,nama', 'items.jenisSampah:id,nama'])
            ->whereNotIn('status', ['selesai', 'dibatalkan', 'ditolak'])
            ->orderByRaw("case status when 'diajukan' then 0 when 'diterima' then 1 else 2 end")->latest()->limit(20)->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'customer' => $s->customer->nama,
                'jenis' => $s->items->pluck('jenisSampah.nama')->join(', '),
                'metode' => $s->metode_penyerahan,
                'jadwal' => $s->jadwal_jemput,
                'status' => $s->status,
                'foto' => $s->fotoUrl(),
                'waktu' => $s->created_at->diffForHumans(),
            ]);

        return Inertia::render('Supplier/Dashboard', [
            'bank' => $this->bank()->only(['id', 'nama']),
            'ringkasan' => [
                'baru' => $q()->where('status', 'diajukan')->count(),
                'perlu_dijemput' => $q()->whereIn('status', ['menunggu_penjemputan', 'dalam_penjemputan'])->count(),
                'perlu_ditimbang' => $q()->where('status', 'diterima')->count(),
                'selesai_bulan_ini' => $q()->where('status', 'selesai')->whereMonth('ditimbang_pada', now()->month)->whereYear('ditimbang_pada', now()->year)->count(),
            ],
            'masuk' => $masuk,
            'notifikasi' => auth()->user()->notifications()->limit(5)->get()->map(fn ($n) => [
                'id' => $n->id, 'judul' => $n->data['judul'] ?? '', 'pesan' => $n->data['pesan'] ?? '',
                'setoran_id' => $n->data['setoran_id'] ?? null, 'dibaca' => $n->read_at !== null, 'waktu' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }
}
