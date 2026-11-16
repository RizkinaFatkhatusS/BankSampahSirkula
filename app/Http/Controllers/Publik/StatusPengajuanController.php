<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\DokumenPengajuan;
use App\Models\PengajuanSupplier;
use App\Services\AkunService;
use App\Services\AuditLogger;
use App\Services\Pengaturan;
use App\Services\PengajuanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/** Cek Status Pengajuan tanpa akun: lewat tautan pribadi (token) atau kode pengajuan + email (FR-46). */
class StatusPengajuanController extends Controller
{
    public function __construct(private PengajuanService $service)
    {
    }

    public function form()
    {
        return Inertia::render('Publik/CekStatus');
    }

    public function cari(Request $request)
    {
        $v = $request->validate(['kode' => ['required', 'string', 'max:30'], 'email' => ['required', 'email', 'max:255']], [], ['kode' => 'kode pengajuan']);

        $p = PengajuanSupplier::where('kode_pengajuan', strtoupper(trim($v['kode'])))->whereRaw('lower(email) = ?', [strtolower($v['email'])])->first();

        if (! $p) {
            // Pesan sama untuk kode/email yang tidak cocok: tidak membocorkan keberadaan data
            return back()->withErrors(['kode' => 'Kode pengajuan dan email tidak cocok.'])->onlyInput('kode', 'email');
        }

        return redirect()->route('pengajuan.status', $p->token_status);
    }

    private function cariToken(string $token): PengajuanSupplier
    {
        return PengajuanSupplier::where('token_status', $token)->firstOrFail();
    }

    public function show(string $token)
    {
        $p = $this->cariToken($token)->load(['dokumen.jenis', 'riwayat', 'user']);
        $aktivasiBisaDiminta = $p->status === 'disetujui' && $p->user && $p->user->status_akun === 'menunggu_aktivasi';

        return Inertia::render('Publik/StatusPengajuan', [
            'token' => $token,
            'pengajuan' => [
                'kode' => $p->kode_pengajuan, 'status' => $p->status, 'label' => $p->labelStatus(),
                'nama_bank_sampah' => $p->nama_bank_sampah, 'alamat' => $p->alamat, 'latitude' => $p->latitude, 'longitude' => $p->longitude,
                'nomor_kontak' => $p->nomor_kontak, 'jam_operasional' => $p->jam_operasional,
                'nama_penanggung_jawab' => $p->nama_penanggung_jawab, 'email' => $p->email, 'nomor_hp' => $p->nomor_hp,
                'catatan_admin' => in_array($p->status, ['perlu_revisi', 'ditolak', 'disetujui'], true) ? $p->catatan_admin : null,
                'dibuat' => $p->created_at->translatedFormat('d F Y, H.i'),
                'batas_revisi_hari' => Pengaturan::get('revisi_kedaluwarsa_hari'),
            ],
            'riwayat' => $p->riwayat->map(fn ($r) => ['status' => $r->status, 'label' => PengajuanSupplier::LABEL[$r->status] ?? $r->status, 'catatan' => $r->catatan, 'waktu' => $r->waktu->translatedFormat('d M Y, H.i')]),
            'dokumen' => $p->dokumen->map(fn ($d) => ['id' => $d->id, 'jenis_id' => $d->jenis_dokumen_id, 'jenis' => $d->jenis?->nama, 'nama' => $d->nama_asli, 'status' => $d->status_pemeriksaan, 'catatan' => $d->catatan]),
            'jenisDokumen' => PendaftaranSupplierController::jenisDokumenProps(),
            'batas' => ['kb' => Pengaturan::maksKb(), 'format' => strtoupper(str_replace(',', ', ', Pengaturan::formatDokumen()))],
            'aktivasiBisaDiminta' => $aktivasiBisaDiminta,
        ]);
    }

    public function kirimUlang(Request $request, string $token)
    {
        $p = $this->cariToken($token);
        $rules = PengajuanService::aturan(false);
        $data = $request->validate($rules, PengajuanService::pesan());

        $this->service->kirimUlang($p, $data, $request->file('dokumen', []));

        return back()->with('success', 'Perbaikan dikirim. Pengajuan Anda kembali menunggu verifikasi.');
    }

    public function aktivasiUlang(string $token)
    {
        $p = $this->cariToken($token)->load('user');

        if ($p->status === 'disetujui' && $p->user && $p->user->status_akun === 'menunggu_aktivasi') {
            AkunService::kirimAktivasi($p->user);
            AuditLogger::catat('users', $p->user->id, 'aktivasi_diminta_ulang_pemilik', null, ['pengajuan' => $p->kode_pengajuan]);
        }

        // Pesan netral agar tidak membocorkan status akun
        return back()->with('success', 'Jika akun Anda belum diaktifkan, tautan aktivasi baru telah dikirim ke email penanggung jawab. Tautan lama tidak berlaku lagi.');
    }

    /** Pemilik pengajuan membuka dokumennya sendiri. */
    public function dokumen(string $token, DokumenPengajuan $dokumen)
    {
        $p = $this->cariToken($token);
        abort_unless($dokumen->pengajuan_id === $p->id, 404);

        return Storage::disk(PengajuanService::DISK)->response($dokumen->path_berkas, $dokumen->nama_asli);
    }
}
