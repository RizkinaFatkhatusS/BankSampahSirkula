<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class LaporanPlatformExport implements WithMultipleSheets
{
    public function __construct(private array $data, private string $judul)
    {
    }

    public function sheets(): array
    {
        $d = $this->data;
        $r = $d['ringkasan'];

        return [
            new LaporanSheet('Ringkasan', [
                [$this->judul], [],
                ['Pengajuan Supplier pada periode', $r['pengajuan']],
                ['Jumlah bank sampah', $r['bank']],
                ['Setoran selesai', $r['setoran']],
                ['Total berat (kg)', $r['berat']],
                ['Total nilai setoran (Rp)', $r['nilai']],
            ]),
            new LaporanSheet('Pengajuan', [
                ['Kode', 'Tanggal', 'Bank sampah', 'Penanggung jawab', 'Status', 'Diputuskan oleh', 'Tanggal putusan', 'Catatan'],
                ...array_map(fn ($x) => [$x['kode'], $x['tanggal'], $x['bank'], $x['penanggung_jawab'], $x['status'], $x['diputuskan_oleh'], $x['diputuskan_pada'], $x['catatan']], $d['pengajuan']),
            ]),
            new LaporanSheet('Bank Sampah', [
                ['Bank sampah', 'Alamat', 'Status akun', 'Status tayang', 'Setoran selesai', 'Berat (kg)', 'Nilai setoran (Rp)', 'Penjualan (Rp)'],
                ...array_map(fn ($x) => [$x['nama'], $x['alamat'], $x['status_akun'], $x['status_tayang'], $x['setoran'], $x['berat'], $x['nilai'], $x['penjualan']], $d['bank']),
            ]),
        ];
    }
}
