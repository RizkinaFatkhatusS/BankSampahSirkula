<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanSupplier;
use App\Models\User;
use App\Services\AkunService;
use App\Services\AuditLogger;
use App\Services\PenghentianService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function __construct(private PenghentianService $henti)
    {
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $daftar = User::with('bankSampah:id,nama,status_tayang')->where('role', User::ROLE_SUPPLIER)
            ->when($status, fn ($x) => $x->where('status_akun', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")
                ->orWhereHas('bankSampah', fn ($b) => $b->where('nama', 'like', "%{$q}%"))))
            ->orderBy('nama')->paginate(15)->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id, 'nama' => $u->nama, 'email' => $u->email, 'bank' => $u->bankSampah?->nama, 'status' => $u->status_akun,
                'tayang' => $u->bankSampah?->status_tayang, 'terakhir_login' => $u->terakhir_login?->diffForHumans(),
            ]);

        return Inertia::render('Admin/Supplier/Index', [
            'supplier' => $daftar,
            'filter' => ['status' => $status, 'q' => $q],
            'hitung' => User::where('role', User::ROLE_SUPPLIER)->selectRaw('status_akun, count(*) as jumlah')->groupBy('status_akun')->pluck('jumlah', 'status_akun'),
        ]);
    }

    public function show(Request $request, User $supplier)
    {
        abort_unless($supplier->isSupplier(), 404);
        $a = $request->user();
        $bank = $supplier->bankSampah;
        $pengajuan = PengajuanSupplier::where('user_id', $supplier->id)->first();

        return Inertia::render('Admin/Supplier/Show', [
            's' => [
                'id' => $supplier->id, 'nama' => $supplier->nama, 'email' => $supplier->email, 'hp' => $supplier->nomor_hp,
                'status' => $supplier->status_akun, 'alasan' => $supplier->alasan_status,
                'batas_penyelesaian' => $supplier->batas_penyelesaian?->translatedFormat('d F Y'),
                'terakhir_login' => $supplier->terakhir_login?->translatedFormat('d M Y, H.i'),
                'terdaftar' => $supplier->created_at->translatedFormat('d M Y'),
            ],
            'bank' => $bank ? ['id' => $bank->id, 'nama' => $bank->nama, 'alamat' => $bank->alamat, 'kontak' => $bank->nomor_kontak, 'jam' => $bank->jam_operasional, 'tayang' => $bank->status_tayang, 'latitude' => $bank->latitude, 'longitude' => $bank->longitude] : null,
            'pengajuanId' => $pengajuan?->id,
            'dampak' => $bank ? $this->henti->dampak($bank->id) : null,
            'tujuan' => PenghentianService::tujuan($supplier->status_akun),
            'hak' => [
                'status' => $a->punya('supplier.status'),
                'nonaktifkan' => $a->punya('supplier.nonaktifkan'),
                'akses' => $a->punya('supplier.akses'),
                'pengajuan' => $a->punya('pengajuan.lihat'),
                'saldo' => $a->punya('banksampah.saldo'),
            ],
        ]);
    }

    public function status(Request $request, User $supplier)
    {
        $v = $request->validate([
            'status' => ['required', 'in:aktif,ditangguhkan,dalam_penyelesaian,nonaktif'],
            'alasan' => ['required', 'string', 'max:1000'],
            'rencana' => ['nullable', 'string', 'max:2000'],
        ], ['alasan.required' => 'Alasan wajib diisi.']);

        $hasil = $this->henti->ubah($request->user(), $supplier, $v['status'], $v['alasan'], $v['rencana'] ?? null);

        return back()->with('success', 'Status akun diperbarui. '.$hasil['customer_terdampak'].' Customer terdampak telah diberi tahu.');
    }

    /** Jalur khusus (hak akses supplier.nonaktifkan): menonaktifkan walau masih ada saldo atau setoran belum final. */
    public function nonaktifkan(Request $request, User $supplier)
    {
        $v = $request->validate(['alasan' => ['required', 'string', 'max:1000'], 'rencana' => ['nullable', 'string', 'max:2000']], ['alasan.required' => 'Alasan wajib diisi.']);
        $hasil = $this->henti->ubah($request->user(), $supplier, 'nonaktif', $v['alasan'], $v['rencana'] ?? null);

        return back()->with('success', 'Akun dinonaktifkan. '.$hasil['customer_terdampak'].' Customer terdampak telah diberi tahu.');
    }

    public function aktivasiUlang(Request $request, User $supplier)
    {
        abort_unless($supplier->isSupplier(), 404);
        if ($supplier->status_akun !== 'menunggu_aktivasi') {
            throw ValidationException::withMessages(['status' => 'Akun ini sudah diaktifkan.']);
        }
        AkunService::kirimAktivasi($supplier);
        AuditLogger::catat('users', $supplier->id, 'aktivasi_dikirim_ulang');

        return back()->with('success', 'Tautan aktivasi baru dikirim. Tautan lama tidak berlaku lagi.');
    }

    public function resetPassword(Request $request, User $supplier)
    {
        abort_unless($supplier->isSupplier(), 404);
        if ($supplier->status_akun === 'menunggu_aktivasi') {
            throw ValidationException::withMessages(['status' => 'Akun belum diaktifkan. Kirim ulang tautan aktivasi.']);
        }
        AkunService::kirimReset($supplier);
        AuditLogger::catat('users', $supplier->id, 'reset_password_dikirim');

        return back()->with('success', 'Tautan reset kata sandi dikirim ke email Supplier. Admin tidak dapat melihat atau menetapkan kata sandi.');
    }
}
