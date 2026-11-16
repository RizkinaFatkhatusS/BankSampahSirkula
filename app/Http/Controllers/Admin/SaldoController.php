<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSampah;
use App\Models\User;
use App\Services\PenghentianService;
use App\Support\Mask;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Daftar Saldo Belum Dicairkan dan ringkasan dampak penghentian bank sampah (FR-50). */
class SaldoController extends Controller
{
    public function index(Request $request, PenghentianService $henti)
    {
        $status = $request->query('status');
        $lengkap = $request->user()->punya('customer.kontak');

        $bank = BankSampah::with('supplier:id,bank_sampah_id,nama,status_akun')->orderBy('nama')->get()
            ->map(function (BankSampah $b) use ($henti, $status, $lengkap) {
                $d = $henti->dampak($b->id);
                $akun = $b->supplier?->status_akun ?? '-';

                return [
                    'id' => $b->id, 'nama' => $b->nama, 'kontak' => $b->nomor_kontak, 'supplier_id' => $b->supplier?->id, 'status_akun' => $akun,
                    'saldo' => $d['saldo_belum_dicairkan'], 'setoran_belum_final' => $d['setoran_belum_final'], 'setoran_per_status' => $d['setoran_per_status'],
                    'customer' => $henti->customerBersaldo($b->id)->map(function ($saldo, $cid) use ($lengkap) {
                        $c = User::find($cid);

                        return ['id' => $cid, 'nama' => $c?->nama, 'kontak' => $lengkap ? $c?->nomor_hp : Mask::hp($c?->nomor_hp), 'saldo' => $saldo];
                    })->values(),
                ];
            })
            ->filter(fn ($r) => ($r['saldo'] > 0 || $r['setoran_belum_final'] > 0) && (! $status || $r['status_akun'] === $status))
            ->sortByDesc('saldo')->values();

        return Inertia::render('Admin/Saldo/Index', [
            'bank' => $bank,
            'total' => round((float) $bank->sum('saldo'), 2),
            'status' => $status,
        ]);
    }
}
