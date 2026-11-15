<?php

namespace App\Http\Controllers\Supplier;

use App\Http\Requests\Supplier\PencairanRequest;
use App\Models\MutasiSaldo;
use App\Models\User;
use App\Services\SetoranService;
use Inertia\Inertia;

class PencairanController extends BaseController
{
    public function __construct(private SetoranService $service)
    {
    }

    /** Customer yang pernah menyetor ke bank sampah ini beserta saldonya. */
    private function customers()
    {
        return User::where('role', 'customer')
            ->whereHas('setoran', fn ($q) => $q->where('bank_sampah_id', $this->bankId())->where('status', 'selesai'))
            ->orderBy('nama')->get();
    }

    public function index()
    {
        $customers = $this->customers();

        return Inertia::render('Supplier/Pencairan', [
            'customers' => $customers->map(fn ($c) => ['id' => $c->id, 'nama' => $c->nama, 'hp' => $c->nomor_hp, 'saldo' => $c->saldo()])->values(),
            'riwayat' => MutasiSaldo::with('customer:id,nama')->where('tipe', 'pencairan')
                ->whereIn('customer_id', $customers->pluck('id'))->orderByDesc('id')->limit(50)->get()
                ->map(fn ($m) => ['id' => $m->id, 'customer' => $m->customer->nama, 'nilai' => (float) $m->nilai, 'cara' => $m->cara_pencairan, 'saldo_setelah' => (float) $m->saldo_setelah, 'tanggal' => $m->tanggal->translatedFormat('d M Y')]),
        ]);
    }

    public function store(PencairanRequest $request)
    {
        $customer = $this->customers()->firstWhere('id', $request->integer('customer_id'));
        abort_unless($customer, 404);

        $this->service->cairkan($request->user(), $customer, (float) $request->input('nilai'), $request->input('cara_pencairan'));

        return back()->with('success', 'Pencairan saldo dicatat.');
    }
}
