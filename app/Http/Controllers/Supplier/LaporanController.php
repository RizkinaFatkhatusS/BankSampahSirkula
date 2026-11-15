<?php

namespace App\Http\Controllers\Supplier;

use App\Exports\LaporanExport;
use App\Models\Laporan;
use App\Services\LaporanService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LaporanController extends BaseController
{
    public function __construct(private LaporanService $service)
    {
    }

    /** Halaman input laporan: pilih periode, lihat pratinjau, simpan, dan unduh. */
    public function index(Request $request)
    {
        $periode = in_array($request->query('periode'), ['mingguan', 'bulanan'], true) ? $request->query('periode') : 'mingguan';
        $acuan = $request->query('tanggal');
        [$mulai, $selesai] = $this->service->rentang($periode, $acuan);
        $data = $this->service->data($this->bankId(), $mulai, $selesai);

        return Inertia::render('Supplier/Laporan', [
            'periode' => $periode,
            'tanggal' => ($acuan ? \Carbon\Carbon::parse($acuan) : now())->toDateString(),
            'rentang' => ['mulai' => $mulai->translatedFormat('d M Y'), 'selesai' => $selesai->translatedFormat('d M Y')],
            'data' => $data,
            'tersimpan' => Laporan::where('bank_sampah_id', $this->bankId())->latest()->limit(20)->get()
                ->map(fn ($l) => [
                    'id' => $l->id, 'periode' => $l->periode, 'mulai' => $l->tanggal_mulai->toDateString(),
                    'label' => $l->tanggal_mulai->translatedFormat('d M').' – '.$l->tanggal_selesai->translatedFormat('d M Y'),
                    'ringkasan' => $l->ringkasan, 'dibuat' => $l->created_at->diffForHumans(),
                ]),
        ]);
    }

    /** Input laporan = menyimpan ringkasan periode agar tercatat (FR-27). */
    public function store(Request $request)
    {
        $v = $request->validate(['periode' => ['required', 'in:mingguan,bulanan'], 'tanggal' => ['required', 'date']]);
        [$mulai, $selesai] = $this->service->rentang($v['periode'], $v['tanggal']);
        $data = $this->service->data($this->bankId(), $mulai, $selesai);

        Laporan::updateOrCreate(
            ['bank_sampah_id' => $this->bankId(), 'periode' => $v['periode'], 'tanggal_mulai' => $mulai->toDateString(), 'tanggal_selesai' => $selesai->toDateString()],
            ['ringkasan' => $data['ringkasan'], 'dibuat_oleh' => $request->user()->id],
        );

        return back()->with('success', 'Laporan periode ini disimpan.');
    }

    /** GET /supplier/laporan/unduh?periode=&format=excel|pdf&tanggal= */
    public function unduh(Request $request)
    {
        $v = $request->validate(['periode' => ['required', 'in:mingguan,bulanan'], 'format' => ['required', 'in:excel,pdf'], 'tanggal' => ['nullable', 'date']]);
        [$mulai, $selesai] = $this->service->rentang($v['periode'], $v['tanggal'] ?? null);
        $bank = $this->bank();
        $data = $this->service->data($bank->id, $mulai, $selesai);
        $judul = 'Laporan '.ucfirst($v['periode']).' '.$bank->nama.' ('.$mulai->translatedFormat('d M Y').' - '.$selesai->translatedFormat('d M Y').')';
        $nama = 'laporan-'.$v['periode'].'-'.$mulai->format('Ymd');

        if ($v['format'] === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new LaporanExport($data, $judul), $nama.'.xlsx');
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.pdf', ['data' => $data, 'judul' => $judul, 'bank' => $bank])
            ->setPaper('a4', 'portrait')->download($nama.'.pdf');
    }
}
