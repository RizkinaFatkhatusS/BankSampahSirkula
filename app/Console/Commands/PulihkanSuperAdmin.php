<?php

namespace App\Console\Commands;

use App\Models\LogAudit;
use App\Models\LogAkses;
use App\Models\User;
use App\Services\AkunService;
use Illuminate\Console\Command;

/**
 * Pemulihan darurat (PRD 5.6) bila seluruh Super Admin kehilangan akses.
 * Dijalankan pengembang di server dan dicatat pada log audit.
 */
class PulihkanSuperAdmin extends Command
{
    protected $signature = 'sirkula:pulihkan-superadmin {email} {--nama=Super Admin : Nama bila akun baru dibuat} {--reset-mfa : Reset autentikasi dua langkah}';

    protected $description = 'Membuat atau memulihkan akun Super Admin dan mengirim tautan atur kata sandi ke emailnya';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $u = User::where('email', $email)->first();

        if ($u && ! $u->isSuperAdmin()) {
            $this->error('Email ini dipakai akun non Super Admin.');

            return self::FAILURE;
        }

        if (! $u) {
            $u = User::create(['nama' => $this->option('nama'), 'email' => $email, 'nomor_hp' => '080000000000', 'password' => null, 'role' => User::ROLE_SUPER_ADMIN, 'status_akun' => 'menunggu_aktivasi']);
            $aksi = 'superadmin_dibuat_cli';
        } else {
            $u->update(['status_akun' => $u->password ? 'aktif' : 'menunggu_aktivasi', 'alasan_status' => null]);
            $aksi = 'superadmin_dipulihkan_cli';
        }
        if ($this->option('reset-mfa')) {
            $u->forceFill(['mfa_aktif' => false, 'mfa_secret' => null])->save();
        }

        $u->password ? AkunService::kirimReset($u) : AkunService::kirimAktivasi($u);

        LogAudit::create(['entitas' => 'users', 'entitas_id' => $u->id, 'user_id' => null, 'aksi' => $aksi, 'nilai_sesudah' => json_encode(['mfa_direset' => (bool) $this->option('reset-mfa')]), 'alasan' => 'Pemulihan darurat melalui Artisan']);
        LogAkses::catat('perubahan_role', ['akun' => $email, 'aksi' => $aksi], $u->id);

        $this->info("Tautan atur kata sandi dikirim ke {$email} (lihat log email / storage/logs bila MAIL_MAILER=log).");

        return self::SUCCESS;
    }
}
