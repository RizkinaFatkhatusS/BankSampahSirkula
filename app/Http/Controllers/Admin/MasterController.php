<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JenisDokumen;
use App\Models\MasterKategori;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Data master (FR-42): jenis dokumen persyaratan, kategori sampah, kategori olahan, satuan. Harga tidak termasuk. */
class MasterController extends Controller
{
    private const GRUP_KATEGORI = ['kategori_sampah', 'kategori_olahan', 'satuan'];

    public function index(Request $request)
    {
        return Inertia::render('Admin/Master/Index', [
            'dokumen' => JenisDokumen::orderBy('urutan')->orderBy('id')->get()->map(fn ($j) => [
                'id' => $j->id, 'nama' => $j->nama, 'keterangan' => $j->keterangan, 'wajib' => $j->wajib, 'aktif' => $j->aktif, 'urutan' => $j->urutan, 'min_berkas' => $j->min_berkas,
            ]),
            'kategori' => collect(self::GRUP_KATEGORI)->mapWithKeys(fn ($g) => [$g => MasterKategori::where('tipe', $g)->orderBy('nama')->get()->map(fn ($m) => ['id' => $m->id, 'kode' => $m->kode, 'nama' => $m->nama, 'aktif' => $m->aktif])]),
            'judulGrup' => MasterKategori::TIPE,
            'bolehKelola' => $request->user()->punya('master.kelola'),
        ]);
    }

    public function store(Request $request)
    {
        $grup = $request->validate(['grup' => ['required', 'in:dokumen,'.implode(',', self::GRUP_KATEGORI)]])['grup'];

        if ($grup === 'dokumen') {
            $v = $this->validasiDokumen($request);
            $m = JenisDokumen::create($v);
            AuditLogger::catat('jenis_dokumen', $m->id, 'dibuat', null, $v);
        } else {
            $v = $request->validate(['nama' => ['required', 'string', 'max:100']]);
            $kode = Str::slug($v['nama'], '_');
            if (MasterKategori::where('tipe', $grup)->where('kode', $kode)->exists()) {
                throw ValidationException::withMessages(['nama' => 'Nama ini sudah ada.']);
            }
            $m = MasterKategori::create(['tipe' => $grup, 'kode' => $kode, 'nama' => $v['nama'], 'aktif' => true]);
            AuditLogger::catat('master_kategori', $m->id, 'dibuat', null, $m->only(['tipe', 'kode', 'nama']));
        }

        return back()->with('success', 'Data master ditambahkan.');
    }

    public function update(Request $request, string $grup, int $id)
    {
        if ($grup === 'dokumen') {
            $m = JenisDokumen::findOrFail($id);
            $v = $this->validasiDokumen($request);
            $sebelum = $m->only(array_keys($v));
            $m->update($v);
            AuditLogger::catat('jenis_dokumen', $m->id, 'diubah', $sebelum, $v);
        } elseif (in_array($grup, self::GRUP_KATEGORI, true)) {
            $m = MasterKategori::where('tipe', $grup)->findOrFail($id);
            $v = $request->validate(['nama' => ['required', 'string', 'max:100'], 'aktif' => ['required', 'boolean']]);
            $sebelum = $m->only(['nama', 'aktif']);
            $m->update($v);
            AuditLogger::catat('master_kategori', $m->id, 'diubah', $sebelum, $v);
        } else {
            abort(404);
        }

        return back()->with('success', 'Data master diperbarui. Pengajuan dan transaksi yang sudah tersimpan tidak berubah.');
    }

    private function validasiDokumen(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:500'],
            'wajib' => ['required', 'boolean'],
            'aktif' => ['required', 'boolean'],
            'urutan' => ['required', 'integer', 'min:0', 'max:999'],
            'min_berkas' => ['required', 'integer', 'min:1', 'max:10'],
        ]);
    }
}
