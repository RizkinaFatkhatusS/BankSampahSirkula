<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Publik\PendaftaranSupplierController;
use App\Models\DokumenPengajuan;
use App\Models\LogAkses;
use App\Models\PengajuanSupplier;
use App\Models\User;
use App\Services\Notifier;
use App\Services\Pengaturan;
use App\Services\PengajuanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PengajuanController extends Controller
{
    public function __construct(private PengajuanService $service)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $daftar = PengajuanSupplier::with('penangan:id,nama')
            ->when($status, fn ($x) => $x->where('status', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('nama_bank_sampah', 'like', "%{$q}%")->orWhere('kode_pengajuan', 'like', "%{$q}%")->orWhere('nama_penanggung_jawab', 'like', "%{$q}%")))
            ->orderByRaw("case status when 'menunggu_verifikasi' then 0 when 'sedang_diverifikasi' then 1 when 'perlu_revisi' then 2 else 3 end")
            ->orderBy('created_at')->paginate(15)->withQueryString()
            ->through(fn ($p) => [
                'id' => $p->id, 'kode' => $p->kode_pengajuan, 'bank' => $p->nama_bank_sampah, 'pj' => $p->nama_penanggung_jawab,
                'status' => $p->status, 'penangan' => $p->penangan?->nama, 'manual' => $p->dibuat_oleh_admin_id !== null,
                'tanggal' => $p->created_at->translatedFormat('d M Y'), 'waktu' => $p->created_at->diffForHumans(),
            ]);

        return Inertia::render('Admin/Pengajuan/Index', [
            'pengajuan' => $daftar,
            'filter' => ['status' => $status, 'q' => $q],
            'hitung' => PengajuanSupplier::selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status'),
        ]);
    }

    public function show(Request $request, PengajuanSupplier $pengajuan)
    {
        $u = $request->user();
        $pengajuan->load(['dokumen.jenis', 'riwayat.pelaku:id,nama', 'penangan:id,nama', 'pembuat:id,nama', 'pemutus:id,nama']);

        $buatSaya = $pengajuan->dibuat_oleh_admin_id === $u->id;
        $sayaPenangan = $pengajuan->ditangani_oleh === $u->id;
        $putus = $u->punya('pengajuan.putuskan');
        $bisaAlihkan = $u->punya('pengajuan.alihkan') && $pengajuan->status === 'sedang_diverifikasi';

        return Inertia::render('Admin/Pengajuan/Show', [
            'p' => [
                'id' => $pengajuan->id, 'kode' => $pengajuan->kode_pengajuan, 'status' => $pengajuan->status,
                'bank' => $pengajuan->nama_bank_sampah, 'alamat' => $pengajuan->alamat, 'latitude' => $pengajuan->latitude, 'longitude' => $pengajuan->longitude,
                'kontak' => $pengajuan->nomor_kontak, 'jam' => $pengajuan->jam_operasional,
                'pj' => $pengajuan->nama_penanggung_jawab, 'email' => $pengajuan->email, 'hp' => $pengajuan->nomor_hp,
                'catatan_admin' => $pengajuan->catatan_admin, 'catatan_verifikasi' => $pengajuan->catatan_verifikasi,
                'penangan' => $pengajuan->penangan?->nama, 'pembuat' => $pengajuan->pembuat?->nama, 'pemutus' => $pengajuan->pemutus?->nama,
                'diputuskan_pada' => $pengajuan->diputuskan_pada?->translatedFormat('d M Y, H.i'),
                'dibuat' => $pengajuan->created_at->translatedFormat('d F Y, H.i'),
                'supplier_id' => $pengajuan->user_id,
            ],
            'dokumen' => $pengajuan->dokumen->map(fn ($d) => [
                'id' => $d->id, 'jenis' => $d->jenis?->nama, 'nama' => $d->nama_asli, 'ukuran' => round($d->ukuran / 1024), 'status' => $d->status_pemeriksaan, 'catatan' => $d->catatan,
            ]),
            'riwayat' => $pengajuan->riwayat->map(fn ($r) => ['status' => $r->status, 'label' => PengajuanSupplier::LABEL[$r->status] ?? $r->status, 'catatan' => $r->catatan, 'oleh' => $r->pelaku?->nama, 'waktu' => $r->waktu->translatedFormat('d M Y, H.i')]),
            'hak' => [
                'dokumen' => $u->punya('pengajuan.dokumen'),
                'mulai' => $putus && $pengajuan->status === 'menunggu_verifikasi' && ! $buatSaya,
                'putuskan' => $putus && $pengajuan->status === 'sedang_diverifikasi' && $sayaPenangan && ! $buatSaya,
                'alihkan' => $bisaAlihkan,
                'buatSaya' => $buatSaya,
                'ditanganiLain' => $pengajuan->status === 'sedang_diverifikasi' && ! $sayaPenangan,
            ],
            'adminTujuan' => $bisaAlihkan
                ? Notifier::adminBerhak('pengajuan.putuskan')->filter(fn ($a) => $a->id !== $pengajuan->dibuat_oleh_admin_id && $a->id !== $pengajuan->ditangani_oleh)->map(fn ($a) => ['id' => $a->id, 'nama' => $a->nama])->values()
                : [],
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Pengajuan/Create', [
            'jenisDokumen' => PendaftaranSupplierController::jenisDokumenProps(),
            'batas' => ['kb' => Pengaturan::maksKb(), 'format' => strtoupper(str_replace(',', ', ', Pengaturan::formatDokumen()))],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(PengajuanService::aturan(), PengajuanService::pesan());
        $p = $this->service->buat($data, $request->file('dokumen', []), $request->user());

        return redirect()->route('admin.pengajuan.show', $p->id)->with('success', "Pengajuan manual dibuat ({$p->kode_pengajuan}). Pengajuan ini harus diverifikasi Admin lain.");
    }

    public function mulai(Request $request, PengajuanSupplier $pengajuan)
    {
        $this->service->mulai($pengajuan, $request->user());

        return back()->with('success', 'Verifikasi dimulai. Pengajuan ini ditandai atas nama Anda.');
    }

    public function periksa(Request $request, PengajuanSupplier $pengajuan, DokumenPengajuan $dokumen)
    {
        abort_unless($dokumen->pengajuan_id === $pengajuan->id, 404);
        $v = $request->validate(['status' => ['required', 'in:belum,sesuai,perlu_diperbaiki'], 'catatan' => ['nullable', 'string', 'max:500']]);
        $this->service->periksaDokumen($pengajuan, $request->user(), $dokumen, $v['status'], $v['catatan'] ?? null);

        return back();
    }

    public function catatan(Request $request, PengajuanSupplier $pengajuan)
    {
        $v = $request->validate(['catatan_verifikasi' => ['nullable', 'string', 'max:2000']]);
        $this->service->catatanVerifikasi($pengajuan, $request->user(), $v['catatan_verifikasi'] ?? null);

        return back()->with('success', 'Catatan verifikasi disimpan.');
    }

    private function putuskan(Request $request, PengajuanSupplier $pengajuan, string $keputusan, string $pesan)
    {
        $v = $request->validate(['catatan' => ['nullable', 'string', 'max:2000']], [], ['catatan' => 'catatan']);
        $this->service->putuskan($pengajuan, $request->user(), $keputusan, $v['catatan'] ?? null);

        return redirect()->route('admin.pengajuan.show', $pengajuan->id)->with('success', $pesan);
    }

    public function setujui(Request $request, PengajuanSupplier $pengajuan)
    {
        return $this->putuskan($request, $pengajuan, 'setujui', 'Pengajuan disetujui. Akun Supplier dibuat dan tautan aktivasi dikirim ke email penanggung jawab.');
    }

    public function revisi(Request $request, PengajuanSupplier $pengajuan)
    {
        return $this->putuskan($request, $pengajuan, 'revisi', 'Permintaan revisi dikirim ke calon Supplier.');
    }

    public function tolak(Request $request, PengajuanSupplier $pengajuan)
    {
        return $this->putuskan($request, $pengajuan, 'tolak', 'Pengajuan ditolak.');
    }

    public function alihkan(Request $request, PengajuanSupplier $pengajuan)
    {
        $v = $request->validate(['target' => ['required', 'integer']]);
        $this->service->alihkan($pengajuan, $request->user(), (int) $v['target']);

        return back()->with('success', 'Pengajuan dialihkan.');
    }

    /** Dokumen verifikasi bersifat rahasia: hanya lewat rute ini, dan pembukaannya dicatat. */
    public function dokumen(Request $request, PengajuanSupplier $pengajuan, DokumenPengajuan $dokumen)
    {
        abort_unless($dokumen->pengajuan_id === $pengajuan->id, 404);
        LogAkses::catat('buka_dokumen', ['pengajuan' => $pengajuan->kode_pengajuan, 'dokumen' => $dokumen->id, 'nama' => $dokumen->nama_asli]);

        return Storage::disk(PengajuanService::DISK)->response($dokumen->path_berkas, $dokumen->nama_asli);
    }
}
