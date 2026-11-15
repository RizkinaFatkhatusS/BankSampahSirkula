<?php

namespace App\Http\Controllers\Supplier;

use App\Models\Mitra;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MitraController extends BaseController
{
    private function rules(): array
    {
        return ['nama' => ['required', 'string', 'max:150'], 'kontak' => ['required', 'string', 'max:30']];
    }

    public function index()
    {
        return Inertia::render('Supplier/Mitra', [
            'mitra' => Mitra::withCount('penjualan')->where('bank_sampah_id', $this->bankId())->orderBy('nama')->get()
                ->map(fn ($m) => ['id' => $m->id, 'nama' => $m->nama, 'kontak' => $m->kontak, 'penjualan' => $m->penjualan_count]),
        ]);
    }

    public function store(Request $request)
    {
        Mitra::create($request->validate($this->rules()) + ['bank_sampah_id' => $this->bankId()]);

        return back()->with('success', 'Mitra ditambahkan.');
    }

    public function update(Request $request, int $mitra)
    {
        Mitra::where('bank_sampah_id', $this->bankId())->findOrFail($mitra)->update($request->validate($this->rules()));

        return back()->with('success', 'Data mitra diperbarui.');
    }

    public function destroy(int $mitra)
    {
        $model = Mitra::where('bank_sampah_id', $this->bankId())->findOrFail($mitra);

        if ($model->penjualan()->exists()) {
            return back()->with('error', 'Mitra sudah punya riwayat penjualan sehingga tidak bisa dihapus.');
        }
        $model->delete();

        return back()->with('success', 'Mitra dihapus.');
    }
}
