<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankSampah;
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

        // Saldo dicatat per bank sampah; tetap terlihat walau bank sampah tidak aktif (FR-03, FR-50)
        $perBank = $user->saldoPerBank();
        $banks = BankSampah::with('supplier:id,bank_sampah_id,status_akun')->whereIn('id', $perBank->pluck('bank_sampah_id')->filter())->get()->keyBy('id');
        $saldoBank = $perBank->map(function ($r) use ($banks) {
            $b = $banks->get($r['bank_sampah_id']);
            $akun = $b?->supplier?->status_akun;

            return [
                'bank_id' => $r['bank_sampah_id'],
                'bank' => $b?->nama ?? 'Bank sampah',
                'kontak' => $b?->nomor_kontak,
                'saldo' => $r['saldo'],
                'tidak_aktif' => $akun !== 'aktif',
                'status_akun' => $akun,
            ];
        })->values();

        return Inertia::render('Customer/Dashboard', [
            'saldo' => $user->saldo(),
            'saldoBank' => $saldoBank,
            'totalBerat' => (float) $user->setoran()->where('status', 'selesai')->sum('total_berat'),
            'totalSetoran' => $user->setoran()->count(),
            'setoranAktif' => $user->setoran()->whereIn('status', ['diajukan', 'disetujui', 'menunggu_penjemputan', 'dalam_penjemputan', 'diterima'])->count(),
            'riwayat' => $riwayat,
        ]);
    }
}
