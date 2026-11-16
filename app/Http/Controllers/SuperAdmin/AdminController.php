<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LogAkses;
use App\Models\Role;
use App\Models\User;
use App\Services\AkunService;
use App\Services\AuditLogger;
use App\Services\EmailService;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Pengelolaan akun Admin dan Super Admin (FR-47). Akun tidak dihapus, hanya dinonaktifkan. */
class AdminController extends Controller
{
    private function stafQuery()
    {
        return User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN]);
    }

    private function jumlahSuperAktif(): int
    {
        return User::where('role', User::ROLE_SUPER_ADMIN)->where('status_akun', 'aktif')->count();
    }

    /** Super Admin terakhir yang aktif tidak boleh dinonaktifkan atau diturunkan. */
    private function lindungiSuperTerakhir(User $target, string $pesan): void
    {
        if ($target->isSuperAdmin() && $target->status_akun === 'aktif' && $this->jumlahSuperAktif() <= 1) {
            throw ValidationException::withMessages(['status' => $pesan]);
        }
    }

    private function konfirmasiSandi(Request $request): void
    {
        if (! Hash::check((string) $request->input('konfirmasi_sandi'), (string) $request->user()->password)) {
            throw ValidationException::withMessages(['konfirmasi_sandi' => 'Kata sandi Anda salah. Konfirmasi ulang diperlukan untuk akun Super Admin.']);
        }
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');

        $daftar = $this->stafQuery()->with('roles:id,nama')
            ->when($status, fn ($x) => $x->where('status_akun', $status))
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w->where('nama', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->orderByRaw("case role when 'super_admin' then 0 else 1 end")->orderBy('nama')->paginate(20)->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id, 'nama' => $u->nama, 'email' => $u->email, 'hp' => $u->nomor_hp, 'role' => $u->role, 'status' => $u->status_akun,
                'roles' => $u->roles->pluck('nama'), 'mfa' => $u->mfa_aktif, 'terakhir_login' => $u->terakhir_login?->diffForHumans(), 'saya' => $u->id === auth()->id(),
            ]);

        return Inertia::render('SuperAdmin/Admin/Index', ['admin' => $daftar, 'filter' => ['q' => $q, 'status' => $status], 'superAktif' => $this->jumlahSuperAktif()]);
    }

    private function rolePilihan()
    {
        return Role::where('sistem', false)->orderBy('nama')->get(['id', 'nama', 'deskripsi', 'aktif']);
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/Admin/Form', ['admin' => null, 'roles' => $this->rolePilihan()]);
    }

    public function store(Request $request)
    {
        $v = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'nomor_hp' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'jenis' => ['required', 'in:admin,super_admin'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('sistem', false)],
        ], ['nomor_hp.regex' => 'Nomor HP tidak valid. Contoh: 081234567890.'], ['nomor_hp' => 'nomor HP']);

        if ($v['jenis'] === 'super_admin') {
            $this->konfirmasiSandi($request);
        }

        $admin = DB::transaction(function () use ($v) {
            $u = User::create([
                'nama' => $v['nama'], 'email' => $v['email'], 'nomor_hp' => $v['nomor_hp'], 'password' => null,
                'role' => $v['jenis'], 'status_akun' => 'menunggu_aktivasi',
            ]);
            if ($v['jenis'] === 'admin') {
                $u->roles()->sync($v['role_ids'] ?? []);
            }
            AuditLogger::catat('users', $u->id, 'akun_admin_dibuat', null, ['jenis' => $v['jenis'], 'role_ids' => $v['role_ids'] ?? []]);
            LogAkses::catat('perubahan_role', ['akun' => $u->email, 'jenis' => $v['jenis'], 'role_ids' => $v['role_ids'] ?? []]);
            AkunService::kirimAktivasi($u);   // undangan: Admin menetapkan kata sandinya sendiri

            return $u;
        });

        return redirect()->route('superadmin.admin.index')->with('success', "Akun {$admin->nama} dibuat dan email undangan dikirim. Kata sandi ditetapkan sendiri oleh yang bersangkutan.");
    }

    public function edit(User $admin)
    {
        abort_unless($admin->isStaf(), 404);

        return Inertia::render('SuperAdmin/Admin/Form', [
            'admin' => ['id' => $admin->id, 'nama' => $admin->nama, 'email' => $admin->email, 'nomor_hp' => $admin->nomor_hp, 'jenis' => $admin->role, 'status' => $admin->status_akun, 'role_ids' => $admin->roles()->pluck('roles.id'), 'saya' => $admin->id === auth()->id()],
            'roles' => $this->rolePilihan(),
        ]);
    }

    public function update(Request $request, User $admin)
    {
        abort_unless($admin->isStaf(), 404);
        $v = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nomor_hp' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'jenis' => ['required', 'in:admin,super_admin'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', Rule::exists('roles', 'id')->where('sistem', false)],
        ], ['nomor_hp.regex' => 'Nomor HP tidak valid. Contoh: 081234567890.'], ['nomor_hp' => 'nomor HP']);

        $jenisBerubah = $v['jenis'] !== $admin->role;
        if ($v['jenis'] === 'super_admin' || $admin->isSuperAdmin()) {
            $this->konfirmasiSandi($request);
        }
        if ($jenisBerubah && $admin->isSuperAdmin()) {
            $this->lindungiSuperTerakhir($admin, 'Tidak dapat menurunkan Super Admin aktif terakhir. Harus ada minimal satu Super Admin aktif.');
        }

        DB::transaction(function () use ($admin, $v, $jenisBerubah) {
            $roleLama = $admin->roles()->pluck('roles.id')->sort()->values()->all();
            $roleBaru = $v['jenis'] === 'admin' ? collect($v['role_ids'] ?? [])->map(fn ($i) => (int) $i)->sort()->values()->all() : [];
            $sebelum = ['nama' => $admin->nama, 'jenis' => $admin->role, 'role_ids' => $roleLama];

            $admin->update(['nama' => $v['nama'], 'nomor_hp' => $v['nomor_hp'], 'role' => $v['jenis']]);
            $admin->roles()->sync($roleBaru);

            AuditLogger::catat('users', $admin->id, 'akun_admin_diubah', $sebelum, ['nama' => $v['nama'], 'jenis' => $v['jenis'], 'role_ids' => $roleBaru]);

            if ($jenisBerubah || $roleLama !== $roleBaru) {
                LogAkses::catat('perubahan_role', ['akun' => $admin->email, 'sebelum' => $roleLama, 'sesudah' => $roleBaru, 'jenis' => $v['jenis']]);
                $namaRole = Role::whereIn('id', $roleBaru)->pluck('nama')->join(', ') ?: ($v['jenis'] === 'super_admin' ? 'Super Admin' : 'tanpa role');
                // Berlaku segera tanpa login ulang; Admin diberi tahu lewat aplikasi dan email (FR-49)
                Notifier::user($admin, 'Role Anda diubah', "Hak akses Anda kini: {$namaRole}.", route('admin.beranda'), 'info');
                EmailService::kirimKeUser($admin, 'role_admin_diubah', 'Role Admin Anda diubah', ["Hak akses akun Admin Anda diperbarui oleh Super Admin. Role Anda sekarang: {$namaRole}.", 'Perubahan berlaku segera.'], 'Buka SIRKULA', route('admin.beranda'));
            }
        });

        return redirect()->route('superadmin.admin.index')->with('success', 'Akun diperbarui. Perubahan role berlaku segera.');
    }

    public function status(Request $request, User $admin)
    {
        abort_unless($admin->isStaf(), 404);
        $v = $request->validate(['status' => ['required', 'in:aktif,nonaktif'], 'alasan' => ['required', 'string', 'max:500']], ['alasan.required' => 'Alasan wajib diisi.']);

        if ($v['status'] === 'nonaktif') {
            $this->lindungiSuperTerakhir($admin, 'Tidak dapat menonaktifkan Super Admin aktif terakhir. Harus ada minimal satu Super Admin aktif.');
        }
        if ($admin->isSuperAdmin()) {
            $this->konfirmasiSandi($request);
        }

        $lama = $admin->status_akun;
        // Akun yang belum pernah diaktifkan kembali ke "menunggu aktivasi", bukan aktif tanpa kata sandi
        $baru = $v['status'] === 'aktif' && ! $admin->password ? 'menunggu_aktivasi' : $v['status'];
        $admin->update(['status_akun' => $baru, 'alasan_status' => $v['alasan']]);
        AuditLogger::catat('users', $admin->id, 'status_akun', ['status_akun' => $lama], ['status_akun' => $baru], $v['alasan']);

        return back()->with('success', $baru === 'nonaktif' ? 'Akun dinonaktifkan. Akses ditutup dan jejak audit tetap utuh.' : 'Akun diaktifkan kembali.');
    }

    public function undangUlang(User $admin)
    {
        abort_unless($admin->isStaf(), 404);
        if ($admin->status_akun !== 'menunggu_aktivasi') {
            throw ValidationException::withMessages(['status' => 'Akun ini sudah diaktifkan.']);
        }
        AkunService::kirimAktivasi($admin);
        AuditLogger::catat('users', $admin->id, 'undangan_dikirim_ulang');

        return back()->with('success', 'Undangan baru dikirim. Tautan lama tidak berlaku lagi.');
    }

    public function resetPassword(User $admin)
    {
        abort_unless($admin->isStaf(), 404);
        if ($admin->status_akun === 'menunggu_aktivasi') {
            throw ValidationException::withMessages(['status' => 'Akun belum diaktifkan. Kirim ulang undangan.']);
        }
        AkunService::kirimReset($admin);
        AuditLogger::catat('users', $admin->id, 'reset_password_dikirim');

        return back()->with('success', 'Tautan reset kata sandi dikirim ke email Admin.');
    }
}
