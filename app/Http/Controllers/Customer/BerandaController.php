<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BerandaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $riwayat = $user->setoran()->with(['items.jenisSampah:id,nama', 'bankSampah:id,nama'])
            ->latest()->limit(5)->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'jenis' => $s->items->pluck('jenisSampah.nama')->join(', '),
                'bank' => $s->bankSampah->nama,
                'berat' => $s->total_berat !== null ? (float) $s->total_berat : null,
                'nilai' => $s->nilai_final !== null ? (float) $s->nilai_final : null,
                'status' => $s->status,
                'tanggal' => $s->created_at->translatedFormat('d M Y'),
            ]);

        return Inertia::render('Customer/Dashboard', [
            'saldo' => $user->saldo(),
            'totalBerat' => (float) $user->setoran()->where('status', 'selesai')->sum('total_berat'),
            'totalSetoran' => $user->setoran()->count(),
            'setoranAktif' => $user->setoran()->whereIn('status', ['diajukan', 'disetujui', 'menunggu_penjemputan', 'dalam_penjemputan', 'diterima'])->count(),
            'riwayat' => $riwayat,
        ]);
    }
}
