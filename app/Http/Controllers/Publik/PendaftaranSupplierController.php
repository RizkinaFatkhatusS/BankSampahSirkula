<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\JenisDokumen;
use App\Services\Pengaturan;
use App\Services\PengajuanService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Halaman publik "Daftar sebagai Supplier" (FR-35). Tidak membuat akun; hanya pengajuan. */
class PendaftaranSupplierController extends Controller
{
    public function __construct(private PengajuanService $service)
    {
    }

    public static function jenisDokumenProps(): array
    {
        return JenisDokumen::aktif()->get()->map(fn ($j) => [
            'id' => $j->id, 'nama' => $j->nama, 'keterangan' => $j->keterangan, 'wajib' => $j->wajib, 'min_berkas' => (int) $j->min_berkas,
        ])->all();
    }

    public function create()
    {
        return Inertia::render('Publik/DaftarSupplier', [
            'jenisDokumen' => self::jenisDokumenProps(),
            'batas' => ['kb' => Pengaturan::maksKb(), 'format' => strtoupper(str_replace(',', ', ', Pengaturan::formatDokumen()))],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(PengajuanService::aturan(), PengajuanService::pesan(), [
            'nama_bank_sampah' => 'nama bank sampah', 'nama_penanggung_jawab' => 'nama penanggung jawab',
            'nomor_hp' => 'nomor HP', 'nomor_kontak' => 'nomor kontak', 'jam_operasional' => 'jam operasional',
        ]);

        $pengajuan = $this->service->buat($data, $request->file('dokumen', []));

        return redirect()->route('pengajuan.status', $pengajuan->token_status)
            ->with('success', "Pengajuan terkirim. Kode pengajuan Anda: {$pengajuan->kode_pengajuan}. Simpan kode ini bersama email Anda.");
    }

    /** Surat pernyataan yang formatnya disediakan sistem (halaman cetak). */
    public function suratPernyataan(Request $request)
    {
        return view('surat-pernyataan', [
            'nama' => $request->query('nama'),
            'bank' => $request->query('bank'),
        ]);
    }
}
