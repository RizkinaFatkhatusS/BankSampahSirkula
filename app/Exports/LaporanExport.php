<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Laporan Excel (maatwebsite/excel): satu file, beberapa sheet. */
class LaporanExport implements WithMultipleSheets
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
                [$this->judul],
                [],
                ['Jumlah setoran selesai', $r['jumlah_setoran']],
                ['Total berat diterima (kg)', $r['total_berat_kg']],
                ['Total nilai setoran (Rp)', $r['total_nilai_setoran']],
                ['Jumlah pengolahan', $r['jumlah_pengolahan']],
                ['Jumlah penjualan', $r['jumlah_penjualan']],
                ['Total penjualan (Rp)', $r['total_penjualan']],
                [],
                ['Komposisi sampah per jenis'],
                ['Jenis', 'Berat (kg)', 'Nilai (Rp)'],
                ...array_map(fn ($x) => [$x['nama'], $x['berat'], $x['nilai']], $d['per_jenis']),
            ]),
            new LaporanSheet('Setoran', [
                ['Tanggal', 'No', 'Customer', 'Jenis', 'Berat (kg)', 'Nilai final (Rp)'],
                ...array_map(fn ($x) => [$x['tanggal'], $x['nomor'], $x['customer'], $x['jenis'], $x['berat'], $x['nilai']], $d['setoran']),
            ]),
            new LaporanSheet('Pengolahan', [
                ['Tanggal', 'Kategori sampah', 'Jenis olahan', 'Berat diolah (kg)', 'Catatan'],
                ...array_map(fn ($x) => [$x['tanggal'], $x['kategori'], $x['olahan'], $x['berat'], $x['catatan']], $d['pengolahan']),
            ]),
            new LaporanSheet('Penjualan', [
                ['Tanggal', 'Mitra', 'Produk', 'Nilai transaksi (Rp)', 'Pengiriman', 'Bukti bayar'],
                ...array_map(fn ($x) => [$x['tanggal'], $x['mitra'], $x['produk'], $x['nilai'], $x['pengiriman'], $x['bukti']], $d['penjualan']),
            ]),
        ];
    }
}

class LaporanSheet implements FromArray, ShouldAutoSize, WithTitle
{
    public function __construct(private string $judul, private array $rows)
    {
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->judul;
    }
}
