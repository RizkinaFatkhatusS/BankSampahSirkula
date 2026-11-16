<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Requests\Supplier\PenjualanRequest;
use App\Models\KasBankSampah;
use App\Models\Mitra;
use App\Models\Penjualan;
use App\Models\ProdukOlahan;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PenjualanController extends BaseController
{
    public function index()
    {
        $bankId = $this->bankId();

        return Inertia::render('Supplier/Penjualan', [
            'penjualan' => Penjualan::with(['mitra:id,nama', 'items.produk:id,nama_produk,satuan'])->where('bank_sampah_id', $bankId)->orderByDesc('tanggal')->orderByDesc('id')->limit(100)->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'mitra' => $p->mitra->nama,
                    'hasil' => $p->hasil_komunikasi,
                    'nilai' => (float) $p->nilai_transaksi,
                    'status' => $p->status_pengiriman,
                    'bukti' => $p->buktiUrl(),
                    'catatan' => $p->catatan,
                    'tanggal' => $p->tanggal->translatedFormat('d M Y'),
                    'items' => $p->items->map(fn ($i) => ['nama' => $i->produk->nama_produk, 'jumlah' => (float) $i->jumlah, 'satuan' => $i->produk->satuan, 'harga' => (float) $i->harga_jual]),
                ]),
            'mitra' => Mitra::where('bank_sampah_id', $bankId)->orderBy('nama')->get(['id', 'nama']),
            'produk' => ProdukOlahan::where('bank_sampah_id', $bankId)->where('jumlah', '>', 0)->orderBy('nama_produk')->get()
                ->map(fn ($p) => ['id' => $p->id, 'nama' => $p->nama_produk, 'stok' => (float) $p->jumlah, 'satuan' => $p->satuan, 'harga' => (float) $p->harga_jual]),
        ]);
    }

    /**
     * Mencatat penjualan ke Mitra. Pembayaran dilakukan di luar sistem dan TIDAK mengubah saldo Customer.
     * Nilai transaksi dihitung dari item agar konsisten; stok produk berkurang bila disetujui.
     */
    public function store(PenjualanRequest $request)
    {
        $bankId = $this->bankId();
        $data = $request->validated();
        $mitra = Mitra::where('bank_sampah_id', $bankId)->findOrFail($data['mitra_id']);
        $disetujui = $data['hasil_komunikasi'] === 'disetujui';

        DB::transaction(function () use ($request, $data, $bankId, $mitra, $disetujui) {
            $nilai = 0;
            $rows = [];
            foreach ($data['items'] as $i => $row) {
                $produk = ProdukOlahan::where('bank_sampah_id', $bankId)->lockForUpdate()->findOrFail($row['produk_olahan_id']);

                if ($disetujui && (float) $row['jumlah'] > (float) $produk->jumlah) {
                    throw ValidationException::withMessages(["items.{$i}.jumlah" => "Stok {$produk->nama_produk} hanya {$produk->jumlah} {$produk->satuan}."]);
                }
                $nilai += round((float) $row['jumlah'] * (float) $row['harga_jual'], 2);
                $rows[] = [$produk, $row];
            }

            $penjualan = Penjualan::create([
                'bank_sampah_id' => $bankId,
                'mitra_id' => $mitra->id,
                'hasil_komunikasi' => $data['hasil_komunikasi'],
                'nilai_transaksi' => $disetujui ? $nilai : 0,
                'bukti_pembayaran' => $request->hasFile('bukti_pembayaran') ? $request->file('bukti_pembayaran')->store('bukti-pembayaran', 'public') : null,
                'status_pengiriman' => $data['status_pengiriman'],
                'catatan' => $data['catatan'] ?? null,
                'tanggal' => $data['tanggal'],
            ]);

            foreach ($rows as [$produk, $row]) {
                $penjualan->items()->create(['produk_olahan_id' => $produk->id, 'jumlah' => $row['jumlah'], 'harga_jual' => $row['harga_jual']]);
                if ($disetujui) {
                    $produk->decrement('jumlah', $row['jumlah']);
                }
            }

            if ($disetujui) {
                KasBankSampah::create(['bank_sampah_id' => $bankId, 'jenis' => 'masuk', 'sumber' => 'penjualan', 'nominal' => $nilai, 'rujukan' => "penjualan#{$penjualan->id}", 'tanggal' => $data['tanggal']]);
            }
            AuditLogger::catat('penjualan', $penjualan->id, 'dibuat', null, ['nilai' => $nilai, 'hasil' => $data['hasil_komunikasi']]);
        });

        return back()->with('success', 'Penjualan dicatat.');
    }

    /** Perbarui status pengiriman dan/atau unggah bukti pembayaran setelahnya. */
    public function update(Request $request, int $penjualan)
    {
        $model = Penjualan::where('bank_sampah_id', $this->bankId())->findOrFail($penjualan);
        $data = $request->validate([
            'status_pengiriman' => ['required', 'in:diproses,dikirim,diterima'],
            'bukti_pembayaran' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:'.\App\Services\Pengaturan::maksKb()],
        ]);

        if ($request->hasFile('bukti_pembayaran')) {
            if ($model->bukti_pembayaran) {
                Storage::disk('public')->delete($model->bukti_pembayaran);
            }
            $data['bukti_pembayaran'] = $request->file('bukti_pembayaran')->store('bukti-pembayaran', 'public');
        } else {
            unset($data['bukti_pembayaran']);
        }
        $model->update($data);

        return back()->with('success', 'Penjualan diperbarui.');
    }
}
