<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Customer/Riwayat', [
            'saldo' => $user->saldo(),
            'setoran' => $user->setoran()->with(['items.jenisSampah:id,nama', 'bankSampah:id,nama'])->latest()->limit(100)->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'jenis' => $s->items->pluck('jenisSampah.nama')->join(', '),
                    'bank' => $s->bankSampah->nama,
                    'berat' => $s->total_berat !== null ? (float) $s->total_berat : null,
                    'estimasi' => (float) $s->estimasi_nilai,
                    'nilai' => $s->nilai_final !== null ? (float) $s->nilai_final : null,
                    'status' => $s->status,
                    'tanggal' => $s->created_at->translatedFormat('d M Y'),
                ]),
            'mutasi' => $user->mutasiSaldo()->with('bank:id,nama')->orderByDesc('id')->limit(100)->get()
                ->map(fn ($m) => [
                    'id' => $m->id,
                    'tipe' => $m->tipe,
                    'nilai' => (float) $m->nilai,
                    'saldo_setelah' => (float) $m->saldo_setelah,
                    'cara' => $m->cara_pencairan,
                    'setoran_id' => $m->setoran_id,
                    'bank' => $m->bank?->nama,
                    'tanggal' => $m->tanggal->translatedFormat('d M Y'),
                ]),
        ]);
    }
}
