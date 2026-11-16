<?php

namespace App\Services;

use App\Models\PengaturanSistem;

/** Parameter operasional yang dapat diatur Super Admin (FR-51). */
class Pengaturan
{
    /** kunci => [nilai bawaan, label, tipe, satuan, minimum] */
    public const DEFAULTS = [
        'aktivasi_jam' => [72, 'Masa berlaku tautan aktivasi', 'int', 'jam', 1],
        'reset_menit' => [60, 'Masa berlaku tautan reset kata sandi', 'int', 'menit', 5],
        'revisi_kedaluwarsa_hari' => [14, 'Batas hari pengajuan Perlu revisi sebelum Kedaluwarsa', 'int', 'hari', 1],
        'verifikasi_target_hari_kerja' => [3, 'Target waktu verifikasi dan pengingat', 'int', 'hari kerja', 1],
        'ditunda_batas_hari' => [7, 'Batas hari setoran Ditunda sebelum dibatalkan otomatis', 'int', 'hari', 1],
        'penyelesaian_hari' => [30, 'Lama masa Dalam penyelesaian', 'int', 'hari', 1],
        'email_percobaan' => [3, 'Jumlah percobaan ulang pengiriman email', 'int', 'kali', 1],
        'berkas_maks_kb' => [2048, 'Ukuran maksimal berkas unggahan', 'int', 'KB', 256],
        'berkas_format_dokumen' => ['pdf,jpg,jpeg,png', 'Format berkas dokumen pendaftaran', 'text', 'pisahkan dengan koma', null],
    ];

    private static ?array $memo = null;

    public static function semua(): array
    {
        if (self::$memo === null) {
            try {
                self::$memo = PengaturanSistem::pluck('nilai', 'kunci')->all();
            } catch (\Throwable) {
                self::$memo = [];
            }
        }

        return self::$memo;
    }

    public static function get(string $kunci): int|string
    {
        $def = self::DEFAULTS[$kunci] ?? null;
        $nilai = self::semua()[$kunci] ?? $def[0] ?? null;

        return ($def[2] ?? 'text') === 'int' ? (int) $nilai : (string) $nilai;
    }

    public static function set(string $kunci, int|string $nilai, ?int $userId = null): void
    {
        PengaturanSistem::updateOrCreate(['kunci' => $kunci], ['nilai' => (string) $nilai, 'diubah_oleh' => $userId, 'updated_at' => now()]);
        self::$memo = null;
    }

    public static function maksKb(): int
    {
        return (int) self::get('berkas_maks_kb');
    }

    public static function formatDokumen(): string
    {
        $f = array_filter(array_map('trim', explode(',', strtolower((string) self::get('berkas_format_dokumen')))));

        return implode(',', $f ?: ['pdf', 'jpg', 'jpeg', 'png']);
    }
}
