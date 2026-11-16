<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanPlatformExport;
use App\Http\Controllers\Controller;
use App\Services\LaporanPlatformService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LaporanController extends Controller
{
    public function __construct(private LaporanPlatformService $service)
    {
    }

    public function index(Request $request)
    {
        $periode = in_array($request->query('periode'), ['mingguan', 'bulanan'], true) ? $request->query('periode') : 'mingguan';
        $acuan = $request->query('tanggal');
        $d = $this->service->laporan($periode, $acuan);

        return Inertia::render('Admin/Laporan', [
            'periode' => $periode,
            'tanggal' => ($acuan ? \Carbon\Carbon::parse($acuan) : now())->toDateString(),
            'rentang' => ['mulai' => $d['mulai']->translatedFormat('d M Y'), 'selesai' => $d['selesai']->translatedFormat('d M Y')],
            'ringkasan' => $d['ringkasan'], 'pengajuan' => $d['pengajuan'], 'bank' => $d['bank'],
        ]);
    }

    /** GET /admin/laporan/unduh?periode=&format=excel|pdf&tanggal= */
    public function unduh(Request $request)
    {
        $v = $request->validate(['periode' => ['required', 'in:mingguan,bulanan'], 'format' => ['required', 'in:excel,pdf'], 'tanggal' => ['nullable', 'date']]);
        $d = $this->service->laporan($v['periode'], $v['tanggal'] ?? null);
        $judul = 'Laporan Platform SIRKULA '.ucfirst($v['periode']).' ('.$d['mulai']->translatedFormat('d M Y').' - '.$d['selesai']->translatedFormat('d M Y').')';
        $nama = 'laporan-platform-'.$v['periode'].'-'.$d['mulai']->format('Ymd');

        if ($v['format'] === 'excel') {
            return \Maatwebsite\Excel\Facades\Excel::download(new LaporanPlatformExport($d, $judul), $nama.'.xlsx');
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.platform-pdf', ['data' => $d, 'judul' => $judul])->setPaper('a4', 'landscape')->download($nama.'.pdf');
    }
}
