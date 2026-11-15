<?php

namespace App\Services;

use App\Models\Pengolahan;
use App\Models\Penjualan;
use App\Models\ProdukOlahan;
use App\Models\Setoran;
use App\Models\SetoranItem;
use Carbon\Carbon;

class LaporanService
{
    /** Rentang tanggal: mingguan = Senin-Minggu pekan yang memuat $acuan; bulanan = 1 bulan penuh. */
    public function rentang(string $periode, ?string $acuan = null): array
    {
        $t = $acuan ? Carbon::parse($acuan) : now();

        return $periode === 'bulanan'
            ? [$t->copy()->startOfMonth()->startOfDay(), $t->copy()->endOfMonth()->endOfDay()]
            : [$t->copy()->startOfWeek()->startOfDay(), $t->copy()->endOfWeek()->endOfDay()];
    }

    public function data(int $bankId, Carbon $mulai, Carbon $selesai): array
    {
        $setoran = Setoran::with(['customer:id,nama', 'items.jenisSampah'])
            ->where('bank_sampah_id', $bankId)->where('status', 'selesai')
            ->whereBetween('ditimbang_pada', [$mulai, $selesai])->orderBy('ditimbang_pada')->get();

        $perJenis = SetoranItem::query()
            ->join('setoran', 'setoran.id', '=', 'setoran_item.setoran_id')
            ->join('jenis_sampah', 'jenis_sampah.id', '=', 'setoran_item.jenis_sampah_id')
            ->where('setoran.bank_sampah_id', $bankId)->where('setoran.status', 'selesai')
            ->whereBetween('setoran.ditimbang_pada', [$mulai, $selesai])
            ->selectRaw('jenis_sampah.nama as nama, SUM(setoran_item.berat_aktual) as berat, SUM(setoran_item.nilai_final) as nilai')
            ->groupBy('jenis_sampah.nama')->get();

        $pengolahan = Pengolahan::where('bank_sampah_id', $bankId)->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])->get();

        $penjualan = Penjualan::with(['mitra:id,nama', 'items.produk:id,nama_produk,satuan'])
            ->where('bank_sampah_id', $bankId)->where('hasil_komunikasi', 'disetujui')
            ->whereBetween('tanggal', [$mulai->toDateString(), $selesai->toDateString()])->orderBy('tanggal')->get();

        return [
            'ringkasan' => [
                'jumlah_setoran' => $setoran->count(),
                'total_berat_kg' => round((float) $setoran->sum('total_berat'), 2),
                'total_nilai_setoran' => round((float) $setoran->sum('nilai_final'), 2),
                'jumlah_pengolahan' => $pengolahan->count(),
                'jumlah_penjualan' => $penjualan->count(),
                'total_penjualan' => round((float) $penjualan->sum('nilai_transaksi'), 2),
            ],
            'per_jenis' => $perJenis->map(fn ($r) => ['nama' => $r->nama, 'berat' => (float) $r->berat, 'nilai' => (float) $r->nilai])->all(),
            'setoran' => $setoran->map(fn ($s) => [
                'tanggal' => $s->ditimbang_pada?->format('d/m/Y'),
                'nomor' => '#'.$s->id,
                'customer' => $s->customer->nama,
                'jenis' => $s->items->pluck('jenisSampah.nama')->join(', '),
                'berat' => (float) $s->total_berat,
                'nilai' => (float) $s->nilai_final,
            ])->all(),
            'pengolahan' => $pengolahan->map(fn ($p) => [
                'tanggal' => $p->tanggal->format('d/m/Y'),
                'kategori' => ucfirst($p->kategori_sampah),
                'olahan' => str_replace('_', ' ', $p->jenis_olahan),
                'berat' => (float) $p->berat_diolah,
                'catatan' => $p->catatan,
            ])->all(),
            'penjualan' => $penjualan->map(fn ($p) => [
                'tanggal' => $p->tanggal->format('d/m/Y'),
                'mitra' => $p->mitra->nama,
                'produk' => $p->items->map(fn ($i) => "{$i->produk->nama_produk} ({$i->jumlah} {$i->produk->satuan})")->join(', '),
                'nilai' => (float) $p->nilai_transaksi,
                'pengiriman' => $p->status_pengiriman,
                'bukti' => $p->bukti_pembayaran ? 'Ada' : 'Belum',
            ])->all(),
        ];
    }

    /** Stok produk saat ini, dipakai halaman statistik. */
    public function stokProduk(int $bankId)
    {
        return ProdukOlahan::where('bank_sampah_id', $bankId)->get();
    }
}
