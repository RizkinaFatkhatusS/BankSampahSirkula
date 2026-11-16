<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    /** Vektor uji RFC 6238 (SHA-1, rahasia ASCII "12345678901234567890"). */
    public function test_sesuai_vektor_rfc_6238(): void
    {
        $rahasia = Totp::base32('12345678901234567890');

        $this->assertSame('GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', $rahasia);
        $this->assertSame('94287082', Totp::kode($rahasia, 59, 8));
        $this->assertSame('07081804', Totp::kode($rahasia, 1111111109, 8));
    }

    public function test_verifikasi_menerima_toleransi_satu_langkah(): void
    {
        $rahasia = Totp::buatRahasia();
        $sekarang = 1_700_000_000;

        $this->assertTrue(Totp::verifikasi($rahasia, Totp::kode($rahasia, $sekarang), $sekarang));
        $this->assertTrue(Totp::verifikasi($rahasia, Totp::kode($rahasia, $sekarang - 30), $sekarang));
        $this->assertFalse(Totp::verifikasi($rahasia, Totp::kode($rahasia, $sekarang - 120), $sekarang));
        $this->assertFalse(Totp::verifikasi($rahasia, '12', $sekarang));
    }
}
