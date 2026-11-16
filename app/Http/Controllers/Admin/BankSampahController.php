<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankSampah;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BankSampahController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $tampil = BankSampah::tampil()->pluck('id')->all();

        $daftar = BankSampah::with('supplier:id,bank_sampah_id,nama,status_akun')
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('alamat', 'like', "%{$q}%")))
            ->orderBy('nama')->paginate(15)->withQueryString()
            ->through(fn ($b) => [
                'id' => $b->id, 'nama' => $b->nama, 'alamat' => $b->alamat, 'latitude' => $b->latitude, 'longitude' => $b->longitude,
                'supplier_id' => $b->supplier?->id, 'status_akun' => $b->supplier?->status_akun ?? '-',
                'status_tayang' => $b->status_tayang, 'tampil' => in_array($b->id, $tampil, true),
            ]);

        return Inertia::render('Admin/BankSampah/Index', [
            'bank' => $daftar, 'q' => $q,
            'bolehKelola' => $request->user()->punya('banksampah.kelola'),
        ]);
    }

    /** Mengubah status tayang (tanpa menghapus data) atau memperbaiki alamat dan titik lokasi yang keliru. */
    public function update(Request $request, BankSampah $bank)
    {
        $v = $request->validate([
            'status_tayang' => ['required', 'in:tayang,tidak_tayang'],
            'alamat' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'alasan' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($v['status_tayang'] !== $bank->status_tayang && blank($v['alasan'] ?? null)) {
            return back()->withErrors(['alasan' => 'Alasan wajib diisi saat mengubah status tayang.']);
        }

        $sebelum = $bank->only(['status_tayang', 'alamat', 'latitude', 'longitude']);
        $bank->update(collect($v)->except('alasan')->all());
        AuditLogger::catat('bank_sampah', $bank->id, 'ubah_data', $sebelum, $bank->only(['status_tayang', 'alamat', 'latitude', 'longitude']), $v['alasan'] ?? null);

        return back()->with('success', 'Data bank sampah diperbarui.');
    }
}
