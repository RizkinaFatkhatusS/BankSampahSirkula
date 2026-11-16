<?php

namespace App\Services;

use App\Models\LogAudit;
use App\Models\TokenAkun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Tautan aktivasi, undangan Admin, dan reset kata sandi: sekali pakai dan berlaku terbatas. */
class AkunService
{
    public static function hash(string $mentah): string
    {
        return hash('sha256', $mentah);
    }

    /** Membuat token baru; token lama yang belum dipakai otomatis tidak berlaku. */
    public static function buatToken(User $user, string $jenis): string
    {
        TokenAkun::where('user_id', $user->id)->whereNull('dipakai_pada')->delete();

        $mentah = Str::random(64);
        TokenAkun::create([
            'user_id' => $user->id,
            'jenis' => $jenis,
            'token_hash' => self::hash($mentah),
            'kadaluarsa_pada' => $jenis === 'reset_password'
                ? now()->addMinutes(Pengaturan::get('reset_menit'))
                : now()->addHours(Pengaturan::get('aktivasi_jam')),
        ]);

        return $mentah;
    }

    public static function temukan(string $mentah): ?TokenAkun
    {
        return TokenAkun::with('user')->where('token_hash', self::hash($mentah))->first();
    }

    /** Aktivasi Supplier (setelah pengajuan disetujui) atau undangan Admin. */
    public static function kirimAktivasi(User $user): void
    {
        $supplier = $user->isSupplier();
        $jenis = $supplier ? 'aktivasi_supplier' : 'undangan_admin';
        $url = route('aktivasi', self::buatToken($user, $jenis));
        $batas = now()->addHours(Pengaturan::get('aktivasi_jam'))->translatedFormat('d F Y, H.i');

        EmailService::kirimKeUser(
            $user,
            $jenis,
            $supplier ? 'Aktivasi akun Supplier SIRKULA' : 'Undangan akun Admin SIRKULA',
            $supplier
                ? ['Halo '.$user->nama.', pendaftaran bank sampah Anda telah disetujui.', 'Silakan tetapkan kata sandi Anda sendiri melalui tautan berikut. Tautan hanya dapat dipakai sekali dan berlaku sampai '.$batas.' WIB.']
                : ['Halo '.$user->nama.', Anda diundang sebagai pengelola platform SIRKULA.', 'Tetapkan kata sandi Anda sendiri melalui tautan berikut. Tautan hanya dapat dipakai sekali dan berlaku sampai '.$batas.' WIB. Administrator tidak mengetahui kata sandi Anda.'],
            'Tetapkan kata sandi',
            $url,
        );
    }

    public static function kirimReset(User $user): void
    {
        $url = route('aktivasi', self::buatToken($user, 'reset_password'));

        EmailService::kirimKeUser(
            $user,
            'reset_password',
            'Atur ulang kata sandi SIRKULA',
            ['Halo '.$user->nama.', kami menerima permintaan untuk mengatur ulang kata sandi akun Anda.', 'Tautan berlaku '.Pengaturan::get('reset_menit').' menit dan hanya dapat dipakai sekali. Abaikan email ini bila Anda tidak memintanya.'],
            'Atur ulang kata sandi',
            $url,
        );
    }

    /** Menetapkan kata sandi dari tautan sekali pakai. Kata sandi hanya diketahui pemilik akun. */
    public static function pakai(TokenAkun $token, string $kataSandi): User
    {
        return DB::transaction(function () use ($token, $kataSandi) {
            $user = $token->user;
            $awal = $user->status_akun === 'menunggu_aktivasi';

            $user->password = $kataSandi;
            if ($awal && $token->jenis !== 'reset_password') {
                $user->status_akun = 'aktif';
                $user->email_verified_at = now();
            }
            $user->save();
            $token->update(['dipakai_pada' => now()]);

            // Aktivasi dilakukan tanpa login, jadi pelaku dicatat langsung dari pemilik akun
            LogAudit::create([
                'entitas' => 'user',
                'entitas_id' => $user->id,
                'user_id' => $user->id,
                'aksi' => $token->jenis === 'reset_password' ? 'reset_password_selesai' : ($awal ? 'aktivasi_akun' : 'atur_kata_sandi'),
                'nilai_sesudah' => json_encode(['status_akun' => $user->status_akun]),
                'ip_address' => request()?->ip(),
            ]);

            if ($awal && $user->isSupplier()) {
                Notifier::user($user, 'Akun Supplier aktif', 'Selamat datang di SIRKULA! Lengkapi profil bank sampah, jenis sampah, dan harga agar bank sampah Anda tampil pada pencarian Customer.', route('supplier.profil'), 'sukses');
                EmailService::kirimKeUser($user, 'akun_supplier_aktif', 'Akun Supplier Anda sudah aktif', ['Akun Anda telah aktif. Silakan masuk, lalu lengkapi profil bank sampah, jenis sampah, dan harga agar bank sampah tampil pada pencarian Customer.'], 'Masuk ke SIRKULA', route('login'));
            }

            return $user;
        });
    }
}
