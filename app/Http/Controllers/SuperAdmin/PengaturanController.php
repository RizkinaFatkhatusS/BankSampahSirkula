<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\Pengaturan;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PengaturanController extends Controller
{
    public function index()
    {
        return Inertia::render('SuperAdmin/Pengaturan', [
            'item' => collect(Pengaturan::DEFAULTS)->map(fn ($d, $k) => [
                'kunci' => $k, 'label' => $d[1], 'tipe' => $d[2], 'satuan' => $d[3], 'min' => $d[4], 'bawaan' => $d[0], 'nilai' => Pengaturan::get($k),
            ])->values(),
        ]);
    }

    public function update(Request $request)
    {
        $aturan = [];
        foreach (Pengaturan::DEFAULTS as $k => $d) {
            $aturan[$k] = $d[2] === 'int' ? ['required', 'integer', 'min:'.$d[4], 'max:100000'] : ['required', 'string', 'regex:/^[a-z0-9]+(,[a-z0-9]+)*$/i', 'max:100'];
        }
        $v = $request->validate($aturan, ['*.regex' => 'Format berkas ditulis dipisah koma, contoh: pdf,jpg,png.']);

        $sebelum = [];
        $sesudah = [];
        foreach ($v as $k => $nilai) {
            $lama = Pengaturan::get($k);
            if ((string) $lama !== (string) $nilai) {
                $sebelum[$k] = $lama;
                $sesudah[$k] = $nilai;
                Pengaturan::set($k, $nilai, $request->user()->id);
            }
        }
        if ($sesudah) {
            AuditLogger::catat('pengaturan_sistem', 0, 'pengaturan_diubah', $sebelum, $sesudah);
        }

        return back()->with('success', $sesudah ? 'Pengaturan disimpan dan berlaku untuk proses berikutnya.' : 'Tidak ada perubahan.');
    }
}
