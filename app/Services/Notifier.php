<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Collection;

class Notifier
{
    /** Admin aktif yang memegang hak akses tertentu (Super Admin selalu termasuk). */
    public static function adminBerhak(string $izin): Collection
    {
        return User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])
            ->where('status_akun', 'aktif')->get()
            ->filter(fn (User $u) => $u->punya($izin))->values();
    }

    /** Notifikasi dalam aplikasi (dan opsional email) ke seluruh Admin berhak. */
    public static function admin(string $izin, string $judul, string $pesan, ?string $url = null, string $tipe = 'info', bool $email = false, string $jenisEmail = 'notifikasi_admin'): void
    {
        foreach (self::adminBerhak($izin) as $a) {
            $a->notify(new PlatformNotification($judul, $pesan, $url, $tipe));
            if ($email) {
                EmailService::kirimKeUser($a, $jenisEmail, $judul, [$pesan], $url ? 'Buka di SIRKULA' : null, $url);
            }
        }
    }

    public static function user(User $user, string $judul, string $pesan, ?string $url = null, string $tipe = 'info'): void
    {
        $user->notify(new PlatformNotification($judul, $pesan, $url, $tipe));
    }
}
