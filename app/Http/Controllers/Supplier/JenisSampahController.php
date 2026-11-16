<?php

namespace App\Http\Controllers\Supplier;

use App\Models\JenisSampah;
use App\Models\MasterKategori;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Inertia\Inertia;

class JenisSampahController extends BaseController
{
    private function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'ikon' => ['nullable', 'string', 'max:8'],
            'kategori' => ['required', Rule::in(array_keys(MasterKategori::daftar('kategori_sampah')))],
            'harga_per_kg' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'aktif' => ['boolean'],
        ];
    }

    public function index()
    {
        return Inertia::render('Supplier/JenisSampah', [
            'jenis' => JenisSampah::where('bank_sampah_id', $this->bankId())->orderBy('nama')->get()
                ->map(fn ($j) => ['id' => $j->id, 'nama' => $j->nama, 'ikon' => $j->ikon, 'kategori' => $j->kategori, 'harga' => (float) $j->harga_per_kg, 'aktif' => $j->aktif, 'berlaku_sejak' => $j->berlaku_sejak?->translatedFormat('d M Y')]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        JenisSampah::create(array_merge($data, [
            'bank_sampah_id' => $this->bankId(),
            'ikon' => ($data['ikon'] ?? null) ?: '♻️',
            'berlaku_sejak' => now()->toDateString(),
        ]));

        return back()->with('success', 'Jenis sampah ditambahkan.');
    }

    public function update(Request $request, int $jenis)
    {
        $model = JenisSampah::where('bank_sampah_id', $this->bankId())->findOrFail($jenis);
        $data = $request->validate($this->rules());
        // Harga berlaku sejak perubahan; setoran lama tidak terpengaruh karena harga disimpan saat penimbangan.
        if ((float) $data['harga_per_kg'] !== (float) $model->harga_per_kg) {
            $data['berlaku_sejak'] = now()->toDateString();
        }
        $model->update(array_merge($data, ['ikon' => ($data['ikon'] ?? null) ?: '♻️']));

        return back()->with('success', 'Jenis sampah diperbarui.');
    }

    public function destroy(int $jenis)
    {
        $model = JenisSampah::where('bank_sampah_id', $this->bankId())->findOrFail($jenis);
        $dipakai = \App\Models\SetoranItem::where('jenis_sampah_id', $model->id)->exists();

        if ($dipakai) {
            $model->update(['aktif' => false]);

            return back()->with('success', 'Jenis ini sudah pernah dipakai di setoran, jadi dinonaktifkan (tidak dihapus).');
        }
        $model->delete();

        return back()->with('success', 'Jenis sampah dihapus.');
    }
}
