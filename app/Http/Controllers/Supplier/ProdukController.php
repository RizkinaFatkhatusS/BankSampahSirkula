<?php

namespace App\Http\Controllers\Supplier;

use App\Models\ProdukOlahan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProdukController extends BaseController
{
    public function index()
    {
        return Inertia::render('Supplier/Produk', [
            'produk' => ProdukOlahan::where('bank_sampah_id', $this->bankId())->orderBy('nama_produk')->get()
                ->map(fn ($p) => ['id' => $p->id, 'nama' => $p->nama_produk, 'kategori' => $p->kategori, 'jumlah' => (float) $p->jumlah, 'satuan' => $p->satuan, 'harga' => (float) $p->harga_jual, 'foto' => $p->fotoUrl()]),
        ]);
    }

    /** POST + _method=PATCH (memuat foto). Stok dan harga jual dapat diubah. */
    public function update(Request $request, int $produk)
    {
        $model = ProdukOlahan::where('bank_sampah_id', $this->bankId())->findOrFail($produk);
        $data = $request->validate([
            'nama_produk' => ['required', 'string', 'max:150'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'satuan' => ['required', 'string', 'max:20'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:'.\App\Services\Pengaturan::maksKb()],
        ]);

        if ($request->hasFile('foto')) {
            if ($model->foto) {
                Storage::disk('public')->delete($model->foto);
            }
            $data['foto'] = $request->file('foto')->store('produk', 'public');
        } else {
            unset($data['foto']);
        }
        $model->update($data);

        return back()->with('success', 'Produk diperbarui.');
    }

    public function destroy(int $produk)
    {
        $model = ProdukOlahan::where('bank_sampah_id', $this->bankId())->findOrFail($produk);

        if ($model->id && \App\Models\PenjualanItem::where('produk_olahan_id', $model->id)->exists()) {
            return back()->with('error', 'Produk sudah tercatat di penjualan sehingga tidak bisa dihapus. Ubah stoknya menjadi 0.');
        }
        $model->delete();

        return back()->with('success', 'Produk dihapus.');
    }
}
