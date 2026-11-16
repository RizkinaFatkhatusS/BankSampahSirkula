<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LogAkses;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use App\Services\EmailService;
use App\Services\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/** Manajemen Role: role tugas = kumpulan hak akses yang dicentang pada checklist (FR-48). */
class RoleController extends Controller
{
    private function katalog(): array
    {
        // Hak akses Khusus Super Admin tidak muncul pada checklist dan tidak dapat didelegasikan
        return Permission::where('jenis', '!=', Permission::KHUSUS_SUPER_ADMIN)->orderBy('id')->get()
            ->groupBy('kelompok')->map(fn ($g) => $g->map(fn ($p) => ['id' => $p->id, 'kode' => $p->kode, 'deskripsi' => $p->deskripsi, 'jenis' => $p->jenis])->values())->all();
    }

    private function kodeRole(Role $r): array
    {
        return $r->permissions()->pluck('kode')->sort()->values()->all();
    }

    public function index()
    {
        $roles = Role::with('admins:id,nama')->withCount('permissions')->orderByDesc('sistem')->orderBy('nama')->get()->map(fn ($r) => [
            'id' => $r->id, 'nama' => $r->nama, 'deskripsi' => $r->deskripsi, 'aktif' => $r->aktif, 'sistem' => $r->sistem,
            'jumlah_izin' => $r->sistem ? Permission::count() : $r->permissions_count,
            'admin' => $r->admins->pluck('nama'),
        ]);

        return Inertia::render('SuperAdmin/Role/Index', ['roles' => $roles]);
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/Role/Form', ['role' => null, 'katalog' => $this->katalog()]);
    }

    private function validasi(Request $request, ?Role $role = null): array
    {
        $v = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:roles,nama'.($role ? ','.$role->id : '')],
            'deskripsi' => ['nullable', 'string', 'max:500'],
            'aktif' => ['required', 'boolean'],
            'izin' => ['nullable', 'array'],
            'izin.*' => ['string'],
        ], [], ['nama' => 'nama role']);

        $valid = Permission::where('jenis', '!=', Permission::KHUSUS_SUPER_ADMIN)->pluck('id', 'kode');
        $v['izin_id'] = collect($v['izin'] ?? [])->filter(fn ($k) => $valid->has($k))->map(fn ($k) => $valid[$k])->unique()->values()->all();

        return $v;
    }

    public function store(Request $request)
    {
        $v = $this->validasi($request);

        $role = DB::transaction(function () use ($v) {
            $r = Role::create(['nama' => $v['nama'], 'deskripsi' => $v['deskripsi'] ?? null, 'aktif' => $v['aktif'], 'sistem' => false]);
            $r->permissions()->sync($v['izin_id']);
            AuditLogger::catat('roles', $r->id, 'role_dibuat', null, ['nama' => $r->nama, 'aktif' => $r->aktif, 'izin' => $this->kodeRole($r)]);
            LogAkses::catat('perubahan_role', ['role' => $r->nama, 'aksi' => 'dibuat']);

            return $r;
        });

        return redirect()->route('superadmin.role.index')->with('success', "Role \"{$role->nama}\" dibuat.");
    }

    public function edit(Role $role)
    {
        abort_if($role->sistem, 403, 'Role sistem Super Admin tidak dapat diubah.');

        return Inertia::render('SuperAdmin/Role/Form', [
            'role' => ['id' => $role->id, 'nama' => $role->nama, 'deskripsi' => $role->deskripsi, 'aktif' => $role->aktif, 'izin' => $this->kodeRole($role), 'admin' => $role->admins()->pluck('nama')],
            'katalog' => $this->katalog(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        abort_if($role->sistem, 403, 'Role sistem Super Admin tidak dapat diubah.');
        $v = $this->validasi($request, $role);

        DB::transaction(function () use ($role, $v) {
            $sebelum = ['nama' => $role->nama, 'aktif' => $role->aktif, 'izin' => $this->kodeRole($role)];
            $role->update(['nama' => $v['nama'], 'deskripsi' => $v['deskripsi'] ?? null, 'aktif' => $v['aktif']]);
            $role->permissions()->sync($v['izin_id']);
            $sesudah = ['nama' => $role->nama, 'aktif' => $role->aktif, 'izin' => $this->kodeRole($role)];

            AuditLogger::catat('roles', $role->id, 'role_diubah', $sebelum, $sesudah);
            LogAkses::catat('perubahan_role', ['role' => $role->nama, 'aksi' => 'diubah']);

            // Admin pemegang role ini diberi tahu; perubahan berlaku segera
            if ($sebelum['izin'] !== $sesudah['izin'] || $sebelum['aktif'] !== $sesudah['aktif']) {
                foreach ($role->admins()->get() as $a) {
                    Notifier::user($a, 'Hak akses role diubah', "Role \"{$role->nama}\" diperbarui oleh Super Admin. Perubahan berlaku segera.", route('admin.beranda'));
                    EmailService::kirimKeUser($a, 'role_admin_diubah', 'Role Admin Anda diubah', ["Role \"{$role->nama}\" yang Anda pegang diperbarui oleh Super Admin. Perubahan hak akses berlaku segera."], 'Buka SIRKULA', route('admin.beranda'));
                }
            }
        });

        return redirect()->route('superadmin.role.index')->with('success', 'Role diperbarui. Perubahan berlaku segera.');
    }

    public function destroy(Role $role)
    {
        abort_if($role->sistem, 403, 'Role sistem Super Admin tidak dapat dihapus.');
        if ($role->admins()->exists()) {
            throw ValidationException::withMessages(['role' => 'Role masih dipakai Admin. Lepaskan dari seluruh Admin sebelum menghapusnya.']);
        }
        $sebelum = ['nama' => $role->nama, 'izin' => $this->kodeRole($role)];
        $role->delete();
        AuditLogger::catat('roles', $role->id, 'role_dihapus', $sebelum, null);
        LogAkses::catat('perubahan_role', ['role' => $sebelum['nama'], 'aksi' => 'dihapus']);

        return redirect()->route('superadmin.role.index')->with('success', 'Role dihapus.');
    }

    public function duplikat(Role $role)
    {
        $nama = $role->nama.' (salinan)';
        $n = 2;
        while (Role::where('nama', $nama)->exists()) {
            $nama = $role->nama.' (salinan '.$n++.')';
        }

        $baru = DB::transaction(function () use ($role, $nama) {
            $r = Role::create(['nama' => $nama, 'deskripsi' => $role->deskripsi, 'aktif' => true, 'sistem' => false]);
            $r->permissions()->sync($role->sistem ? Permission::where('jenis', '!=', Permission::KHUSUS_SUPER_ADMIN)->pluck('id') : $role->permissions()->pluck('permissions.id'));
            AuditLogger::catat('roles', $r->id, 'role_digandakan', ['dari' => $role->nama], ['nama' => $r->nama, 'izin' => $this->kodeRole($r)]);

            return $r;
        });

        return redirect()->route('superadmin.role.edit', $baru->id)->with('success', 'Role digandakan. Ubah sesuai kebutuhan.');
    }
}
