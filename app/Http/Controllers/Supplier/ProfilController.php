<?php

namespace App\Http\Controllers\Supplier;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfilController extends BaseController
{
    public function edit()
    {
        $b = $this->bank();

        return Inertia::render('Supplier/Profil', [
            'bank' => ['nama' => $b->nama, 'alamat' => $b->alamat, 'latitude' => $b->latitude, 'longitude' => $b->longitude, 'nomor_kontak' => $b->nomor_kontak, 'jam_operasional' => $b->jam_operasional, 'foto' => $b->fotoUrl()],
        ]);
    }

    /** Dikirim sebagai POST + _method=PATCH karena memuat unggahan foto. */
    public function update(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'alamat' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'nomor_kontak' => ['required', 'string', 'max:30'],
            'jam_operasional' => ['required', 'string', 'max:100'],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:'.\App\Services\Pengaturan::maksKb()],
        ]);

        $bank = $this->bank();
        if ($request->hasFile('foto')) {
            if ($bank->foto) {
                Storage::disk('public')->delete($bank->foto);
            }
            $data['foto'] = $request->file('foto')->store('bank-sampah', 'public');
        } else {
            unset($data['foto']);
        }
        $bank->update($data);

        return back()->with('success', 'Profil bank sampah diperbarui.');
    }
}
