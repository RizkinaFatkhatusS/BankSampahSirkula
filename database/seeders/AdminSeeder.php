<?php

namespace Database\Seeders;

use App\Models\DokumenPengajuan;
use App\Models\JenisDokumen;
use App\Models\PengajuanSupplier;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Akun Super Admin pertama dan contoh Admin (PRD FR-01: Super Admin pertama dibuat lewat seeder).
 * Kata sandi contoh: "password". GANTI sebelum dipakai sungguhan.
 * Idempotent: aman dijalankan ulang  ->  php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(HakAksesSeeder::class);
        $sandi = env('SUPERADMIN_PASSWORD', 'password');

        foreach ([['Super Admin Utama', 'superadmin@sirkula.test'], ['Super Admin Cadangan', 'superadmin2@sirkula.test']] as $i => [$nama, $email]) {
            $this->akun($nama, $email, '08120000000'.($i + 1), User::ROLE_SUPER_ADMIN, $sandi, []);
        }

        $this->akun('Dewi Verifikator', 'verifikator@sirkula.test', '081200000011', User::ROLE_ADMIN, 'password', ['Verifikator Supplier']);
        $this->akun('Rudi Pengelola Akun', 'pengelola@sirkula.test', '081200000012', User::ROLE_ADMIN, 'password', ['Pengelola Akun', 'Penanganan Penutupan Bank Sampah', 'Pengelola Data Master']);
        $this->akun('Sari Pemantau', 'pemantau@sirkula.test', '081200000013', User::ROLE_ADMIN, 'password', ['Pemantau', 'Auditor']);

        $this->contohPengajuan();
    }

    private function akun(string $nama, string $email, string $hp, string $role, string $sandi, array $namaRole): void
    {
        $u = User::updateOrCreate(['email' => $email], [
            'nama' => $nama, 'nomor_hp' => $hp, 'role' => $role, 'status_akun' => 'aktif',
            'password' => $sandi, 'email_verified_at' => now(),
        ]);
        $u->roles()->sync(Role::whereIn('nama', $namaRole)->pluck('id'));
    }

    private function contohPengajuan(): void
    {
        if (PengajuanSupplier::where('email', 'calon@sirkula.test')->exists()) {
            return;
        }

        $p = PengajuanSupplier::create([
            'kode_pengajuan' => 'SRK-DEMO2345',
            'token_status' => 'demo-token-status-pengajuan-sirkula-0123456789abcd',
            'nama_bank_sampah' => 'Bank Sampah Cempaka Asri',
            'alamat' => 'Jl. Cempaka No. 21, Cimanggis, Depok',
            'latitude' => -6.3650, 'longitude' => 106.8450,
            'nomor_kontak' => '081377700011', 'jam_operasional' => '08.00 – 15.00 WIB',
            'nama_penanggung_jawab' => 'Bu Lestari', 'email' => 'calon@sirkula.test', 'nomor_hp' => '081377700011',
            'status' => 'menunggu_verifikasi',
        ]);

        foreach (JenisDokumen::aktif()->get() as $j) {
            for ($i = 1; $i <= $j->min_berkas; $i++) {
                $path = "pengajuan/{$p->id}/demo-{$j->id}-{$i}.txt";
                Storage::disk('local')->put($path, "Contoh berkas demo untuk dokumen: {$j->nama}");
                DokumenPengajuan::create(['pengajuan_id' => $p->id, 'jenis_dokumen_id' => $j->id, 'path_berkas' => $path, 'nama_asli' => 'contoh-'.str($j->nama)->slug().".txt", 'ukuran' => 64, 'status_pemeriksaan' => 'belum']);
            }
        }
        $p->riwayat()->create(['status' => 'menunggu_verifikasi', 'catatan' => 'Pengajuan dikirim (data contoh).', 'waktu' => now()]);
    }
}
