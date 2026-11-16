<?php

namespace App\Services;

use App\Jobs\KirimEmailJob;
use App\Models\LogEmail;
use App\Models\User;
use Throwable;

class EmailService
{
    /**
     * Catat email pada log_email lalu kirim lewat antrean setelah transaksi DB berhasil.
     */
    public static function kirim(string $jenis, string $penerima, string $subjek, array $baris, ?string $tombolTeks = null, ?string $tombolUrl = null, ?string $catatan = null, ?int $userId = null, ?int $pengajuanId = null): LogEmail
    {
        $log = LogEmail::create([
            'jenis' => $jenis,
            'penerima' => $penerima,
            'user_id' => $userId,
            'pengajuan_id' => $pengajuanId,
            'status' => 'antre',
            'muatan' => ['subjek' => $subjek, 'baris' => $baris, 'tombol_teks' => $tombolTeks, 'tombol_url' => $tombolUrl, 'catatan' => $catatan],
        ]);

        self::antre($log);

        return $log;
    }

    public static function kirimKeUser(User $user, string $jenis, string $subjek, array $baris, ?string $tombolTeks = null, ?string $tombolUrl = null): LogEmail
    {
        return self::kirim($jenis, $user->email, $subjek, $baris, $tombolTeks, $tombolUrl, null, $user->id);
    }

    /** Kirim ulang (mis. dari log email). Percobaan dihitung ulang dari awal. */
    public static function kirimUlang(LogEmail $log): void
    {
        $log->update(['status' => 'antre', 'percobaan' => 0, 'pesan_error' => null]);
        self::antre($log);
    }

    private static function antre(LogEmail $log): void
    {
        try {
            KirimEmailJob::dispatch($log->id)->afterCommit();
        } catch (Throwable) {
            // Kegagalan sudah dicatat pada log_email oleh KirimEmailJob::failed(); alur utama tidak boleh terganggu.
        }
    }
}
