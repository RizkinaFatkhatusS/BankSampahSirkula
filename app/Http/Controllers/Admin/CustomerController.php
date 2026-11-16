<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AkunService;
use App\Services\AuditLogger;
use App\Services\EmailService;
use App\Support\Mask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/** Admin mengelola status akun Customer; tidak mengubah saldo, riwayat, maupun data pribadi (FR-40). */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $lengkap = $request->user()->punya('customer.kontak');

        $daftar = User::where('role', User::ROLE_CUSTOMER)
            ->when($status, fn ($x) => $x->where('status_akun', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderByDesc('created_at')->paginate(20)->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id, 'nama' => $u->nama,
                'email' => $lengkap ? $u->email : Mask::email($u->email),
                'hp' => $lengkap ? $u->nomor_hp : Mask::hp($u->nomor_hp),
                'status' => $u->status_akun, 'alasan' => $u->alasan_status, 'terdaftar' => $u->created_at->translatedFormat('d M Y'),
            ]);

        return Inertia::render('Admin/Customer/Index', [
            'customer' => $daftar,
            'filter' => ['status' => $status, 'q' => $q],
            'kontakLengkap' => $lengkap,
            'bolehStatus' => $request->user()->punya('customer.status'),
        ]);
    }

    public function status(Request $request, User $customer)
    {
        abort_unless($customer->isCustomer(), 404);
        $v = $request->validate(['status' => ['required', 'in:aktif,nonaktif'], 'alasan' => ['required', 'string', 'max:1000']], ['alasan.required' => 'Alasan wajib diisi.']);

        DB::transaction(function () use ($customer, $v) {
            $lama = $customer->status_akun;
            $customer->update(['status_akun' => $v['status'], 'alasan_status' => $v['alasan']]);
            AuditLogger::catat('users', $customer->id, 'status_akun', ['status_akun' => $lama], ['status_akun' => $v['status']], $v['alasan']);

            if ($v['status'] === 'nonaktif') {
                EmailService::kirimKeUser($customer, 'akun_customer_nonaktif', 'Akun SIRKULA Anda dinonaktifkan', ["Akun Anda dinonaktifkan. Alasan: {$v['alasan']}", 'Saldo dan riwayat setoran Anda tidak hilang dan tetap tercatat. Hubungi Admin SIRKULA bila Anda merasa ini keliru.']);
            }
        });

        return back()->with('success', $v['status'] === 'nonaktif' ? 'Akun Customer dinonaktifkan. Saldo dan riwayat tidak berubah.' : 'Akun Customer diaktifkan kembali.');
    }

    public function resetPassword(Request $request, User $customer)
    {
        abort_unless($customer->isCustomer(), 404);
        AkunService::kirimReset($customer);
        AuditLogger::catat('users', $customer->id, 'reset_password_dikirim');

        return back()->with('success', 'Tautan reset kata sandi dikirim ke email Customer.');
    }
}
