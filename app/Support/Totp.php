<?php

namespace App\Support;

/** TOTP (RFC 6238) tanpa paket tambahan: SHA-1, 6 digit, langkah 30 detik. */
final class Totp
{
    private const ALFABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function buatRahasia(int $byte = 20): string
    {
        return self::base32(random_bytes($byte));
    }

    public static function base32(string $biner): string
    {
        $bit = '';
        foreach (str_split($biner) as $c) {
            $bit .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }
        $out = '';
        foreach (str_split($bit, 5) as $potong) {
            $out .= self::ALFABET[bindec(str_pad($potong, 5, '0'))];
        }

        return $out;
    }

    public static function dekode(string $b32): string
    {
        $bit = '';
        foreach (str_split(strtoupper(rtrim($b32, '='))) as $c) {
            $p = strpos(self::ALFABET, $c);
            if ($p !== false) {
                $bit .= str_pad(decbin($p), 5, '0', STR_PAD_LEFT);
            }
        }
        $out = '';
        foreach (str_split($bit, 8) as $byte) {
            if (strlen($byte) === 8) {
                $out .= chr(bindec($byte));
            }
        }

        return $out;
    }

    public static function kode(string $rahasia, ?int $waktu = null, int $digit = 6): string
    {
        $counter = intdiv($waktu ?? time(), 30);
        $hash = hash_hmac('sha1', pack('N', 0).pack('N', $counter), self::dekode($rahasia), true);
        $offset = ord($hash[19]) & 0x0f;
        $nilai = ((ord($hash[$offset]) & 0x7f) << 24)
            | ((ord($hash[$offset + 1]) & 0xff) << 16)
            | ((ord($hash[$offset + 2]) & 0xff) << 8)
            | (ord($hash[$offset + 3]) & 0xff);

        return str_pad((string) ($nilai % (10 ** $digit)), $digit, '0', STR_PAD_LEFT);
    }

    /** Toleransi ±1 langkah (30 detik) untuk selisih jam perangkat. */
    public static function verifikasi(string $rahasia, string $kode, ?int $waktu = null): bool
    {
        $kode = preg_replace('/\D/', '', $kode);
        if (strlen($kode) !== 6) {
            return false;
        }
        $waktu ??= time();
        foreach ([-1, 0, 1] as $geser) {
            if (hash_equals(self::kode($rahasia, $waktu + $geser * 30), $kode)) {
                return true;
            }
        }

        return false;
    }

    public static function uri(string $akun, string $penerbit, string $rahasia): string
    {
        return 'otpauth://totp/'.rawurlencode($penerbit.':'.$akun).'?secret='.$rahasia.'&issuer='.rawurlencode($penerbit).'&algorithm=SHA1&digits=6&period=30';
    }
}
