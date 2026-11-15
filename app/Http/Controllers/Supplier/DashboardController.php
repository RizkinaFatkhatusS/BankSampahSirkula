<?php

namespace App\Http\Controllers\Supplier;

use App\Models\KasBankSampah;
use App\Models\Penjualan;
use App\Models\ProdukOlahan;
use App\Models\Setoran;
use App\Models\SetoranItem;
use Inertia\Inertia;

/** Dashboard statistik: khusus Supplier (FR-29). Datanya hanya milik bank sampah yang dikelola. */
class DashboardController extends BaseController
{
    public function index()
    {
        $bankId = $this->bankId();
        $enamBulan = now()->subMonths(5)->startOfMonth();

        $setoran = Setoran::where('bank_sampah_id', $bankId)->where('status', 'selesai')->get(['id', 'total_berat', 'nilai_final', 'ditimbang_pada']);
        $penjualan = Penjualan::where('bank_sampah_id', $bankId)->where('hasil_komunikasi', 'disetujui')->get(['nilai_transaksi', 'tanggal', 'status_pengiriman']);

        $bulan = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->startOfMonth());
        $perBulan = $bulan->map(fn ($b) => [
            'label' => $b->translatedFormat('M Y'),
            'berat' => round((float) $setoran->filter(fn ($s) => $s->ditimbang_pada && $s->ditimbang_pada->isSameMonth($b))->sum('total_berat'), 2),
            'penjualan' => round((float) $penjualan->filter(fn ($p) => $p->tanggal->isSameMonth($b))->sum('nilai_transaksi'), 2),
        ]);

        $komposisiSampah = SetoranItem::query()
            ->join('setoran', 'setoran.id', '=', 'setoran_item.setoran_id')
            ->join('jenis_sampah', 'jenis_sampah.id', '=', 'setoran_item.jenis_sampah_id')
            ->where('setoran.bank_sampah_id', $bankId)->where('setoran.status', 'selesai')
            ->selectRaw('jenis_sampah.nama as nama, SUM(setoran_item.berat_aktual) as berat')
            ->groupBy('jenis_sampah.nama')->get()
            ->map(fn ($r) => ['nama' => $r->nama, 'berat' => round((float) $r->berat, 2)]);

        $labelOlahan = ['pupuk_cair' => 'Pupuk cair', 'kerajinan' => 'Kerajinan', 'energi_lain' => 'Energi lain'];
        $komposisiProduk = ProdukOlahan::where('bank_sampah_id', $bankId)->get()->groupBy('kategori')
            ->map(fn ($g, $k) => ['nama' => $labelOlahan[$k] ?? $k, 'jumlah' => round((float) $g->sum('jumlah'), 2), 'jenis' => $g->count()])->values();

        $kasMasuk = (float) KasBankSampah::where('bank_sampah_id', $bankId)->where('jenis', 'masuk')->sum('nominal');
        $kasKeluar = (float) KasBankSampah::where('bank_sampah_id', $bankId)->where('jenis', 'keluar')->sum('nominal');

        return Inertia::render('Supplier/Statistik', [
            'total' => [
                'sampah_kg' => round((float) $setoran->sum('total_berat'), 2),
                'sampah_bulan_ini_kg' => round((float) $setoran->filter(fn ($s) => $s->ditimbang_pada?->isSameMonth(now()))->sum('total_berat'), 2),
                'setoran_selesai' => $setoran->count(),
                'nilai_setoran' => round((float) $setoran->sum('nilai_final'), 2),
                'penjualan_nilai' => round((float) $penjualan->sum('nilai_transaksi'), 2),
                'penjualan_jumlah' => $penjualan->count(),
                'penjualan_diterima' => $penjualan->where('status_pengiriman', 'diterima')->count(),
                'kas_masuk' => $kasMasuk,
                'kas_keluar' => $kasKeluar,
            ],
            'perBulan' => $perBulan,
            'komposisiSampah' => $komposisiSampah,
            'komposisiProduk' => $komposisiProduk,
        ]);
    }
}
