<?php

return [
    // Autentikasi dua langkah (TOTP) wajib untuk Super Admin dan Admin pemegang hak akses sensitif.
    // Set MFA_WAJIB=false di .env hanya untuk pengembangan lokal.
    'mfa_wajib' => (bool) env('MFA_WAJIB', true),

    // Batas waktu sesi tidak aktif untuk Admin dan Super Admin (menit)
    'sesi_staf_menit' => (int) env('SESI_STAF_MENIT', 30),

    // Penerbit pada aplikasi autentikator
    'mfa_penerbit' => env('APP_NAME', 'SIRKULA'),
];
