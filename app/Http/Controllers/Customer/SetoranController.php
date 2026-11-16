<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreSetoranRequest;
use App\Models\BankSampah;
use App\Models\Setoran;
use App\Services\SetoranService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetoranController extends Controller
{
    public function __construct(private SetoranService $service)
    {
    }

    /** Form bertahap. Bank sampah dipilih lebih dulu dari halaman pencarian (?bank=ID). */
    public function create(Request $request)
    {
        $bank = BankSampah::tampil()->find($request->query('bank'));

        if (! $bank) {
            return redirect()->route('customer.bank.index')->with('error', 'Pilih bank sampah terlebih dahulu. Bank sampah yang dipilih sedang tidak menerima setoran.');
        }

        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        // Bank sampah terdekat dari posisi Customer untuk info "Antar Sendiri" (OI-03): bisa berbeda dari bank terpilih.
        $terdekat = null;
        if ($lat !== null && $lng !== null) {
            $terdekat = BankSampah::tampil()->get()
                ->map(fn ($b) => ['b' => $b, 'jarak' => BankSampah::jarakKm($lat, $lng, $b->latitude, $b->longitude)])
                ->sortBy('jarak')->first();
        }
        $fmt = fn (BankSampah $b, ?float $jarak = null) => [
            'id' => $b->id, 'nama' => $b->nama, 'alamat' => $b->alamat, 'jam' => $b->jam_operasional,
            'kontak' => $b->nomor_kontak, 'latitude' => $b->latitude, 'longitude' => $b->longitude, 'jarak' => $jarak,
        ];

        return Inertia::render('Customer/Setor', [
            'bank' => $fmt($bank),
            'bankTerdekat' => $terdekat && $terdekat['b']->id !== $bank->id ? $fmt($terdekat['b'], $terdekat['jarak']) : null,
            'jenisSampah' => $bank->jenisSampah()->where('aktif', true)->orderBy('nama')->get()
                ->map(fn ($j) => ['id' => $j->id, 'nama' => $j->nama, 'ikon' => $j->ikon, 'kategori' => $j->kategori, 'harga' => (float) $j->harga_per_kg]),
            'alamatDefault' => '',
        ]);
    }

    public function store(StoreSetoranRequest $request)
    {
        $bank = BankSampah::findOrFail($request->integer('bank_sampah_id'));
        $setoran = $this->service->ajukan($request->user(), $bank, $request->validated(), $request->file('foto_bukti'));

        return redirect()->route('customer.setoran.show', $setoran)->with('success', 'Setoran berhasil diajukan.');
    }

    public function show(Request $request, Setoran $setoran)
    {
        $this->authorize('view', $setoran);
        $setoran->load(['items.jenisSampah', 'bankSampah', 'riwayatPenjemputan']);

        return Inertia::render('Customer/SetoranShow', [
            'setoran' => [
                'id' => $setoran->id,
                'status' => $setoran->status,
                'metode' => $setoran->metode_penyerahan,
                'alamat_jemput' => $setoran->alamat_jemput,
                'jadwal_jemput' => $setoran->jadwal_jemput,
                'alasan_penolakan' => $setoran->alasan_penolakan,
                'estimasi' => (float) $setoran->estimasi_nilai,
                'nilai_final' => $setoran->nilai_final !== null ? (float) $setoran->nilai_final : null,
                'total_berat' => $setoran->total_berat !== null ? (float) $setoran->total_berat : null,
                'foto' => $setoran->fotoUrl(),
                'tanggal' => $setoran->created_at->translatedFormat('d F Y, H.i'),
                'bisa_batal' => $request->user()->can('batal', $setoran),
                'bank' => ['nama' => $setoran->bankSampah->nama, 'alamat' => $setoran->bankSampah->alamat, 'jam' => $setoran->bankSampah->jam_operasional, 'kontak' => $setoran->bankSampah->nomor_kontak, 'latitude' => $setoran->bankSampah->latitude, 'longitude' => $setoran->bankSampah->longitude],
                'items' => $setoran->items->map(fn ($i) => [
                    'id' => $i->id, 'nama' => $i->jenisSampah->nama, 'ikon' => $i->jenisSampah->ikon,
                    'harga' => (float) ($i->harga_per_kg ?? $i->jenisSampah->harga_per_kg),
                    'berat' => $i->berat_aktual !== null ? (float) $i->berat_aktual : null,
                    'nilai' => $i->nilai_final !== null ? (float) $i->nilai_final : null,
                ]),
                'riwayat_penjemputan' => $setoran->riwayatPenjemputan->map(fn ($r) => ['status' => $r->status, 'waktu' => $r->waktu_perubahan->translatedFormat('d M Y, H.i')]),
            ],
        ]);
    }

    public function batal(Request $request, Setoran $setoran)
    {
        $this->authorize('batal', $setoran);
        $this->service->batalkan($setoran, $request->user());

        return back()->with('success', 'Setoran dibatalkan.');
    }
}
