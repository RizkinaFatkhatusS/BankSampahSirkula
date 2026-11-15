<?php

namespace App\Http\Controllers\Supplier;

use App\Models\Pengolahan;
use App\Models\Setoran;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PengolahanController extends BaseController
{
    public function index()
    {
        $bankId = $this->bankId();

        return Inertia::render('Supplier/Olah', [
            'pengolahan' => Pengolahan::with(['produk', 'setoranAsal:id'])->where('bank_sampah_id', $bankId)->orderByDesc('tanggal')->orderByDesc('id')->limit(50)->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'kategori_sampah' => $p->kategori_sampah,
                    'jenis_olahan' => $p->jenis_olahan,
                    'berat' => $p->berat_diolah !== null ? (float) $p->berat_diolah : null,
                    'tanggal' => $p->tanggal->translatedFormat('d M Y'),
                    'catatan' => $p->catatan,
                    'foto' => $p->fotoUrl(),
                    'setoran' => $p->setoranAsal->pluck('id'),
                    'produk' => $p->produk->map(fn ($x) => ['id' => $x->id, 'nama' => $x->nama_produk, 'jumlah' => (float) $x->jumlah, 'satuan' => $x->satuan, 'harga' => (float) $x->harga_jual]),
                ]),
            // Setoran yang sudah ditimbang dapat dipilih sebagai asal bahan (penelusuran)
            'setoranTersedia' => Setoran::with(['customer:id,nama', 'items.jenisSampah:id,nama,kategori'])
                ->where('bank_sampah_id', $bankId)->where('status', 'selesai')->latest('ditimbang_pada')->limit(40)->get()
                ->map(fn ($s) => ['id' => $s->id, 'label' => "#{$s->id} · {$s->customer->nama} · ".$s->items->pluck('jenisSampah.nama')->join(', ').' · '.(float) $s->total_berat.' kg']),
        ]);
    }

    /** Mencatat pemilahan, pengolahan, dan barang jadi sekaligus (FR-22, FR-23). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'kategori_sampah' => ['required', 'in:organik,anorganik'],
            'jenis_olahan' => ['required', 'in:pupuk_cair,kerajinan,energi_lain'],
            'berat_diolah' => ['nullable', 'numeric', 'min:0'],
            'tanggal' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'foto_dokumentasi' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'setoran_ids' => ['nullable', 'array'],
            'setoran_ids.*' => ['integer'],
            'produk' => ['required', 'array', 'min:1'],
            'produk.*.nama_produk' => ['required', 'string', 'max:150'],
            'produk.*.jumlah' => ['required', 'numeric', 'gt:0'],
            'produk.*.satuan' => ['required', 'string', 'max:20'],
            'produk.*.harga_jual' => ['required', 'numeric', 'min:0'],
        ], [
            'foto_dokumentasi.required' => 'Foto dokumentasi barang jadi wajib diunggah.',
            'produk.required' => 'Catat minimal satu barang jadi.',
        ], ['foto_dokumentasi' => 'foto dokumentasi']);

        $bankId = $this->bankId();
        $setoranIds = Setoran::where('bank_sampah_id', $bankId)->whereIn('id', $data['setoran_ids'] ?? [])->pluck('id');

        DB::transaction(function () use ($request, $data, $bankId, $setoranIds) {
            $p = Pengolahan::create([
                'bank_sampah_id' => $bankId,
                'kategori_sampah' => $data['kategori_sampah'],
                'jenis_olahan' => $data['jenis_olahan'],
                'berat_diolah' => $data['berat_diolah'] ?? null,
                'tanggal' => $data['tanggal'],
                'catatan' => $data['catatan'] ?? null,
                'foto_dokumentasi' => $request->file('foto_dokumentasi')->store('pengolahan', 'public'),
            ]);
            $p->setoranAsal()->sync($setoranIds);

            foreach ($data['produk'] as $row) {
                $p->produk()->create([
                    'bank_sampah_id' => $bankId,
                    'nama_produk' => $row['nama_produk'],
                    'kategori' => $data['jenis_olahan'],
                    'jumlah' => $row['jumlah'],
                    'satuan' => $row['satuan'],
                    'harga_jual' => $row['harga_jual'],
                ]);
            }
            AuditLogger::catat('pengolahan', $p->id, 'dibuat', null, ['produk' => count($data['produk'])]);
        });

        return back()->with('success', 'Pengolahan dan barang jadi tercatat.');
    }
}
