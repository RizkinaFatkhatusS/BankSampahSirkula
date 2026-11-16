<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BankSampah;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankSampahController extends Controller
{
    /** Pencarian nama/lokasi + filter Terdekat / Organik / Anorganik, lengkap dengan jarak (FR-04). */
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $filter = $request->query('filter');
        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        $banks = BankSampah::tampil()->with(['jenisSampah' => fn ($j) => $j->where('aktif', true)])
            ->when($q !== '', fn ($b) => $b->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('alamat', 'like', "%{$q}%")))
            ->when(in_array($filter, ['organik', 'anorganik'], true),
                fn ($b) => $b->whereHas('jenisSampah', fn ($j) => $j->where('aktif', true)->where('kategori', $filter)))
            ->get()
            ->map(fn (BankSampah $b) => [
                'id' => $b->id,
                'nama' => $b->nama,
                'alamat' => $b->alamat,
                'latitude' => $b->latitude,
                'longitude' => $b->longitude,
                'jam' => $b->jam_operasional,
                'kontak' => $b->nomor_kontak,
                'foto' => $b->fotoUrl(),
                'jarak' => ($lat !== null && $lng !== null) ? BankSampah::jarakKm($lat, $lng, $b->latitude, $b->longitude) : null,
                'jenis' => $b->jenisSampah->pluck('nama')->values(),
            ]);

        $banks = ($filter === 'terdekat' && $lat !== null)
            ? $banks->sortBy('jarak')->values()
            : $banks->sortBy('nama')->values();

        return Inertia::render('Customer/CariLokasi', [
            'banks' => $banks,
            'filters' => ['q' => $q, 'filter' => $filter, 'lat' => $lat, 'lng' => $lng],
        ]);
    }

    public function show(Request $request, BankSampah $bank)
    {
        abort_unless(BankSampah::tampil()->whereKey($bank->id)->exists(), 404);

        $lat = is_numeric($request->query('lat')) ? (float) $request->query('lat') : null;
        $lng = is_numeric($request->query('lng')) ? (float) $request->query('lng') : null;

        return Inertia::render('Customer/BankSampahDetail', [
            'bank' => [
                'id' => $bank->id,
                'nama' => $bank->nama,
                'alamat' => $bank->alamat,
                'latitude' => $bank->latitude,
                'longitude' => $bank->longitude,
                'jam' => $bank->jam_operasional,
                'kontak' => $bank->nomor_kontak,
                'foto' => $bank->fotoUrl(),
                'jarak' => ($lat !== null && $lng !== null) ? BankSampah::jarakKm($lat, $lng, $bank->latitude, $bank->longitude) : null,
            ],
            'jenisSampah' => $bank->jenisSampah()->where('aktif', true)->orderBy('nama')->get()
                ->map(fn ($j) => ['id' => $j->id, 'nama' => $j->nama, 'ikon' => $j->ikon, 'kategori' => $j->kategori, 'harga' => (float) $j->harga_per_kg]),
            'koordinat' => ['lat' => $lat, 'lng' => $lng],
        ]);
    }
}
