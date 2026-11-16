<?php

namespace App\Services;

use App\Models\BankSampah;
use App\Models\PengajuanSupplier;
use App\Models\Penjualan;
use App\Models\Setoran;
use App\Models\User;
use Carbon\Carbon;

/** Data laporan dan dashboard platform (FR-43, FR-44). Hanya agregat; tanpa data pribadi/saldo Customer. */
class LaporanPlatformService
{
    public function rentang(string $periode, ?string $acuan = null): array
    {
        return (new LaporanService)->rentang($periode, $acuan);
    }

    /** Ringkasan per bank sampah (baca saja). */
    public function perBank(?Carbon $mulai = null, ?Carbon $selesai = null): array
    {
        return BankSampah::with('supplier:id,bank_sampah_id,status_akun')->orderBy('nama')->get()->map(function (BankSampah $b) use ($mulai, $selesai) {
            $q = Setoran::where('bank_sampah_id', $b->id)->where('status', 'selesai')
                ->when($mulai, fn ($x) => $x->whereBetween('ditimbang_pada', [$mulai, $selesai]));

            return [
                'id' => $b->id,
                'nama' => $b->nama,
                'alamat' => $b->alamat,
                'status_akun' => $b->supplier?->status_akun ?? '-',
                'status_tayang' => $b->status_tayang,
                'setoran' => (clone $q)->count(),
                'berat' => round((float) (clone $q)->sum('total_berat'), 2),
                'nilai' => round((float) (clone $q)->sum('nilai_final'), 2),
                'penjualan' => round((float) Penjualan::where('bank_sampah_id', $b->id)->where('hasil_komunikasi', 'disetujui')
                    ->when($mulai, fn ($x) => $x->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()]))->sum('nilai_transaksi'), 2),
            ];
        })->all();
    }

    public function dashboard(): array
    {
        $pengajuan = PengajuanSupplier::selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status')->map(fn ($j) => (int) $j)->all();
        $akun = User::where('role', User::ROLE_SUPPLIER)->selectRaw('status_akun, count(*) as jumlah')->groupBy('status_akun')->pluck('jumlah', 'status_akun')->map(fn ($j) => (int) $j)->all();

        $setoran = Setoran::where('status', 'selesai')->get(['total_berat', 'nilai_final', 'ditimbang_pada']);
        $bulan = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());

        return [
            'pengajuan' => $pengajuan,
            'akunSupplier' => $akun,
            'jumlahCustomer' => User::where('role', User::ROLE_CUSTOMER)->count(),
            'jumlahBank' => BankSampah::count(),
            'totalBerat' => round((float) $setoran->sum('total_berat'), 2),
            'totalNilai' => round((float) $setoran->sum('nilai_final'), 2),
            'penjualanNilai' => round((float) Penjualan::where('hasil_komunikasi', 'disetujui')->sum('nilai_transaksi'), 2),
            'penjualanJumlah' => Penjualan::where('hasil_komunikasi', 'disetujui')->count(),
            'perBulan' => $bulan->map(fn ($b) => [
                'label' => $b->translatedFormat('M Y'),
                'berat' => round((float) $setoran->filter(fn ($s) => $s->ditimbang_pada && $s->ditimbang_pada->isSameMonth($b))->sum('total_berat'), 2),
            ])->all(),
            'perBank' => $this->perBank(),
        ];
    }

    public function laporan(string $periode, ?string $acuan): array
    {
        [$mulai, $selesai] = $this->rentang($periode, $acuan);

        $pengajuan = PengajuanSupplier::with('pemutus:id,nama')->whereBetween('created_at', [$mulai, $selesai])->orderBy('created_at')->get()
            ->map(fn ($p) => [
                'kode' => $p->kode_pengajuan, 'tanggal' => $p->created_at->format('d/m/Y'), 'bank' => $p->nama_bank_sampah,
                'penanggung_jawab' => $p->nama_penanggung_jawab, 'status' => $p->labelStatus(),
                'diputuskan_oleh' => $p->pemutus?->nama ?? '-', 'diputuskan_pada' => $p->diputuskan_pada?->format('d/m/Y') ?? '-',
                'catatan' => $p->catatan_admin ?? '',
            ])->all();

        $bank = $this->perBank($mulai, $selesai);

        return [
            'mulai' => $mulai, 'selesai' => $selesai, 'pengajuan' => $pengajuan, 'bank' => $bank,
            'ringkasan' => [
                'pengajuan' => count($pengajuan),
                'bank' => count($bank),
                'setoran' => array_sum(array_column($bank, 'setoran')),
                'berat' => round(array_sum(array_column($bank, 'berat')), 2),
                'nilai' => round(array_sum(array_column($bank, 'nilai')), 2),
            ],
        ];
    }
}
