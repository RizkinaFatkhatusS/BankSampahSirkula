<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Requests\Supplier\TimbangRequest;
use App\Models\Setoran;
use App\Services\SetoranService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SetoranController extends BaseController
{
    public function __construct(private SetoranService $service)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');

        $list = Setoran::with(['customer:id,nama', 'items.jenisSampah:id,nama'])
            ->where('bank_sampah_id', $this->bankId())
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()->limit(100)->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'customer' => $s->customer->nama,
                'jenis' => $s->items->pluck('jenisSampah.nama')->join(', '),
                'metode' => $s->metode_penyerahan,
                'status' => $s->status,
                'estimasi' => (float) $s->estimasi_nilai,
                'nilai' => $s->nilai_final !== null ? (float) $s->nilai_final : null,
                'tanggal' => $s->created_at->translatedFormat('d M Y'),
            ]);

        return Inertia::render('Supplier/Setoran/Index', ['setoran' => $list, 'status' => $status]);
    }

    public function show(Setoran $setoran)
    {
        $this->authorize('view', $setoran);
        $setoran->load(['customer', 'items.jenisSampah', 'riwayatPenjemputan']);

        return Inertia::render('Supplier/Setoran/Show', [
            'setoran' => [
                'id' => $setoran->id,
                'status' => $setoran->status,
                'metode' => $setoran->metode_penyerahan,
                'alamat_jemput' => $setoran->alamat_jemput,
                'jadwal_jemput' => $setoran->jadwal_jemput,
                'alasan_penolakan' => $setoran->alasan_penolakan,
                'foto' => $setoran->fotoUrl(),
                'estimasi' => (float) $setoran->estimasi_nilai,
                'total_berat' => $setoran->total_berat !== null ? (float) $setoran->total_berat : null,
                'nilai_final' => $setoran->nilai_final !== null ? (float) $setoran->nilai_final : null,
                'tanggal' => $setoran->created_at->translatedFormat('d F Y, H.i'),
                'customer' => ['nama' => $setoran->customer->nama, 'hp' => $setoran->customer->nomor_hp],
                'items' => $setoran->items->map(fn ($i) => [
                    'id' => $i->id, 'nama' => $i->jenisSampah->nama, 'ikon' => $i->jenisSampah->ikon,
                    'harga_sekarang' => (float) $i->jenisSampah->harga_per_kg,
                    'harga' => $i->harga_per_kg !== null ? (float) $i->harga_per_kg : null,
                    'berat' => $i->berat_aktual !== null ? (float) $i->berat_aktual : null,
                    'nilai' => $i->nilai_final !== null ? (float) $i->nilai_final : null,
                ]),
                'riwayat_penjemputan' => $setoran->riwayatPenjemputan->map(fn ($r) => ['status' => $r->status, 'waktu' => $r->waktu_perubahan->translatedFormat('d M Y, H.i')]),
            ],
        ]);
    }

    public function setujui(Request $request, Setoran $setoran)
    {
        $this->authorize('kelola', $setoran);
        $this->service->setujui($setoran, $request->user());

        return back()->with('success', 'Setoran disetujui.');
    }

    public function terima(Request $request, Setoran $setoran)
    {
        $this->authorize('kelola', $setoran);
        $this->service->terima($setoran, $request->user());

        return back()->with('success', 'Sampah ditandai sudah diterima. Silakan timbang.');
    }

    public function tolak(Request $request, Setoran $setoran)
    {
        $this->authorize('kelola', $setoran);
        $data = $request->validate(['alasan_penolakan' => ['required', 'string', 'max:500']], [], ['alasan_penolakan' => 'alasan penolakan']);
        $this->service->tolak($setoran, $request->user(), $data['alasan_penolakan']);

        return back()->with('success', 'Setoran ditolak.');
    }

    public function penjemputan(Request $request, Setoran $setoran)
    {
        $this->authorize('kelola', $setoran);
        $data = $request->validate(['status' => ['required', 'in:menunggu_penjemputan,dalam_penjemputan,selesai']]);
        $this->service->ubahPenjemputan($setoran, $request->user(), $data['status']);

        return back()->with('success', 'Status penjemputan diperbarui.');
    }

    public function timbang(TimbangRequest $request, Setoran $setoran)
    {
        $this->authorize('kelola', $setoran);
        $hasil = $this->service->timbang($setoran, $request->user(), $request->validated()['berat']);

        return redirect()->route('supplier.setoran.show', $setoran)
            ->with('success', 'Penimbangan tersimpan. Nilai final '.SetoranService::rp($hasil->nilai_final).' ditambahkan ke saldo Customer.');
    }
}
