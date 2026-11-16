<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\JenisDokumen;
use App\Models\JenisSampah;
use App\Models\MutasiSaldo;
use App\Models\PengajuanSupplier;
use App\Models\Role;
use App\Models\Setoran;
use App\Models\User;
use App\Services\AkunService;
use App\Services\PenghentianService;
use App\Services\PengajuanService;
use Database\Seeders\HakAksesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['sirkula.mfa_wajib' => false]);
        Storage::fake('local');
        $this->seed(HakAksesSeeder::class);
    }

    private function admin(string ...$role): User
    {
        $u = User::factory()->admin()->create();
        $u->roles()->sync(Role::whereIn('nama', $role)->pluck('id'));

        return $u;
    }

    private function dataPengajuan(string $email = 'calon@example.test'): array
    {
        return [
            'nama_bank_sampah' => 'Bank Sampah Uji', 'alamat' => 'Jl. Uji 1', 'latitude' => -6.4, 'longitude' => 106.8,
            'nomor_kontak' => '081234567890', 'jam_operasional' => '08-16', 'nama_penanggung_jawab' => 'Pak Uji',
            'email' => $email, 'nomor_hp' => '081234567890',
        ];
    }

    private function berkas(): array
    {
        return JenisDokumen::aktif()->get()->mapWithKeys(fn ($j) => [
            $j->id => array_map(fn ($i) => UploadedFile::fake()->create("dok{$i}.pdf", 50, 'application/pdf'), range(1, $j->min_berkas)),
        ])->all();
    }

    private function pengajuan(?User $pembuat = null): PengajuanSupplier
    {
        return app(PengajuanService::class)->buat($this->dataPengajuan(), $this->berkas(), $pembuat);
    }

    // ------------------------------------------------------------ hak akses

    public function test_admin_tanpa_hak_akses_ditolak_dan_tercatat(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.pengajuan.index'))->assertForbidden();
        $this->assertDatabaseHas('log_akses', ['jenis' => 'penolakan_403', 'user_id' => $admin->id]);
    }

    public function test_role_memberi_akses_sesuai_checklist(): void
    {
        $verifikator = $this->admin('Verifikator Supplier');

        $this->actingAs($verifikator)->get(route('admin.pengajuan.index'))->assertOk();
        $this->actingAs($verifikator)->get(route('admin.supplier.index'))->assertForbidden();        // bukan haknya
        $this->actingAs($verifikator)->get(route('superadmin.role.index'))->assertForbidden();       // khusus Super Admin
    }

    public function test_super_admin_memiliki_seluruh_hak_akses(): void
    {
        $super = User::factory()->superAdmin()->create();

        foreach (['admin.pengajuan.index', 'admin.supplier.index', 'admin.log-audit', 'superadmin.role.index', 'superadmin.pengaturan', 'superadmin.log-akses'] as $rute) {
            $this->actingAs($super)->get(route($rute))->assertOk();
        }
    }

    public function test_perubahan_role_berlaku_segera(): void
    {
        $admin = $this->admin('Pemantau');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        Role::where('nama', 'Pemantau')->first()->update(['aktif' => false]);

        $this->actingAs($admin->fresh())->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_customer_dan_supplier_tidak_bisa_masuk_area_admin(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.beranda'))->assertForbidden();
        $this->actingAs(User::factory()->supplier()->create())->get(route('admin.beranda'))->assertForbidden();
    }

    public function test_2fa_wajib_untuk_super_admin(): void
    {
        config(['sirkula.mfa_wajib' => true]);
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->get(route('admin.beranda'))->assertRedirect(route('mfa.show'));
    }

    // ------------------------------------------------------------ pendaftaran Supplier

    public function test_pengajuan_dibuat_dengan_kode_dan_dokumen_private(): void
    {
        $p = $this->pengajuan();

        $this->assertSame('menunggu_verifikasi', $p->status);
        $this->assertMatchesRegularExpression('/^SRK-[A-Z0-9]{8}$/', $p->kode_pengajuan);
        $this->assertGreaterThan(0, $p->dokumen()->count());
        $this->assertDatabaseHas('log_email', ['jenis' => 'pengajuan_diterima', 'penerima' => 'calon@example.test']);
        Storage::disk('local')->assertExists($p->dokumen()->first()->path_berkas);
    }

    public function test_email_dengan_pengajuan_aktif_ditolak(): void
    {
        $this->pengajuan();

        $this->expectException(ValidationException::class);
        $this->pengajuan();
    }

    public function test_persetujuan_membuat_akun_supplier_dan_bank_sampah(): void
    {
        $p = $this->pengajuan();
        $verifikator = $this->admin('Verifikator Supplier');
        $service = app(PengajuanService::class);

        $service->mulai($p, $verifikator);
        $service->putuskan($p->fresh(), $verifikator, 'setujui', null);

        $supplier = User::where('email', 'calon@example.test')->first();
        $this->assertSame('supplier', $supplier->role);
        $this->assertSame('menunggu_aktivasi', $supplier->status_akun);
        $this->assertNull($supplier->password);
        $this->assertNotNull($supplier->bank_sampah_id);
        $this->assertSame('disetujui', $p->fresh()->status);
        $this->assertDatabaseHas('log_email', ['jenis' => 'aktivasi_supplier']);
    }

    public function test_aktivasi_menetapkan_sandi_sendiri_dan_token_sekali_pakai(): void
    {
        $supplier = User::factory()->supplier()->create(['status_akun' => 'menunggu_aktivasi', 'password' => null]);
        $token = AkunService::buatToken($supplier, 'aktivasi_supplier');

        $this->post(route('aktivasi.store', $token), ['password' => 'sandi-rahasia-1', 'password_confirmation' => 'sandi-rahasia-1'])->assertRedirect(route('login'));
        $this->assertSame('aktif', $supplier->fresh()->status_akun);

        $this->get(route('aktivasi', $token))->assertOk();                       // halaman tampil, tetapi sudah tidak berlaku
        $this->post(route('aktivasi.store', $token), ['password' => 'lain-lagi-123', 'password_confirmation' => 'lain-lagi-123'])->assertStatus(410);
    }

    public function test_tautan_aktivasi_lama_tidak_berlaku_setelah_dikirim_ulang(): void
    {
        $supplier = User::factory()->supplier()->create(['status_akun' => 'menunggu_aktivasi', 'password' => null]);
        $lama = AkunService::buatToken($supplier, 'aktivasi_supplier');
        AkunService::buatToken($supplier, 'aktivasi_supplier');

        $this->assertNull(AkunService::temukan($lama));
    }

    public function test_dua_pasang_mata_pembuat_tidak_boleh_memutuskan_pengajuan_manualnya(): void
    {
        $verifikator = $this->admin('Verifikator Supplier');
        $p = $this->pengajuan($verifikator);

        $this->expectException(ValidationException::class);
        app(PengajuanService::class)->mulai($p, $verifikator);
    }

    public function test_admin_lain_tidak_bisa_memutuskan_kecuali_dialihkan(): void
    {
        $a = $this->admin('Verifikator Supplier');
        $b = $this->admin('Verifikator Supplier');
        $service = app(PengajuanService::class);
        $p = $this->pengajuan();
        $service->mulai($p, $a);

        try {
            $service->putuskan($p->fresh(), $b, 'tolak', 'tes');
            $this->fail('Admin lain seharusnya ditolak.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $service->alihkan($p->fresh(), $this->admin('Verifikator Supplier'), $b->id);
        $service->putuskan($p->fresh(), $b, 'tolak', 'Dokumen tidak valid');
        $this->assertSame('ditolak', $p->fresh()->status);
    }

    public function test_revisi_wajib_bercatatan_dan_hanya_dokumen_bertanda_yang_diganti(): void
    {
        $verifikator = $this->admin('Verifikator Supplier');
        $service = app(PengajuanService::class);
        $p = $this->pengajuan();
        $service->mulai($p, $verifikator);

        $dok = $p->dokumen()->first();
        $service->periksaDokumen($p->fresh(), $verifikator, $dok, 'perlu_diperbaiki', 'Kurang jelas');

        try {
            $service->putuskan($p->fresh(), $verifikator, 'revisi', '');
            $this->fail('Catatan revisi wajib.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $service->putuskan($p->fresh(), $verifikator, 'revisi', 'Foto KTP kurang jelas');
        $this->assertSame('perlu_revisi', $p->fresh()->status);

        // Kirim ulang tanpa mengganti dokumen yang ditandai harus gagal
        $this->expectException(ValidationException::class);
        $service->kirimUlang($p->fresh(), $this->dataPengajuan(), []);
    }

    public function test_pengajuan_perlu_revisi_kedaluwarsa_setelah_batas_hari(): void
    {
        $p = $this->pengajuan();
        $p->update(['status' => 'perlu_revisi']);
        $p->riwayat()->create(['status' => 'perlu_revisi', 'waktu' => now()->subDays(20)]);

        $this->assertSame(1, app(PengajuanService::class)->kedaluwarsakan());
        $this->assertSame('kedaluwarsa', $p->fresh()->status);
    }

    public function test_dokumen_pengajuan_hanya_bisa_dibuka_pemegang_izin_dan_tercatat(): void
    {
        $p = $this->pengajuan();
        $dok = $p->dokumen()->first();
        $tanpaIzin = $this->admin('Pemantau');
        $dokumenIzin = $this->admin('Verifikator Supplier');

        $this->actingAs($tanpaIzin)->get(route('admin.pengajuan.dokumen', [$p, $dok]))->assertForbidden();
        $this->actingAs($dokumenIzin)->get(route('admin.pengajuan.dokumen', [$p, $dok]))->assertOk();
        $this->assertDatabaseHas('log_akses', ['jenis' => 'buka_dokumen', 'user_id' => $dokumenIzin->id]);
    }

    // ------------------------------------------------------------ penghentian bank sampah

    private function bankDenganSupplier(): array
    {
        $bank = BankSampah::create(['nama' => 'Bank B', 'alamat' => 'Jl. B', 'latitude' => -6.4, 'longitude' => 106.8, 'nomor_kontak' => '0812', 'jam_operasional' => '08-16']);
        $supplier = User::factory()->supplier($bank->id)->create();
        JenisSampah::create(['bank_sampah_id' => $bank->id, 'nama' => 'Plastik', 'kategori' => 'anorganik', 'harga_per_kg' => 2000]);
        $customer = User::factory()->create();

        return [$bank, $supplier, $customer];
    }

    private function setoran(BankSampah $bank, User $customer, string $status = 'diajukan'): Setoran
    {
        return Setoran::create(['customer_id' => $customer->id, 'bank_sampah_id' => $bank->id, 'foto_bukti' => 'x.png', 'estimasi_nilai' => 2000, 'metode_penyerahan' => 'antar_sendiri', 'status' => $status]);
    }

    private function saldo(User $customer, BankSampah $bank, float $nilai): void
    {
        MutasiSaldo::create(['customer_id' => $customer->id, 'bank_sampah_id' => $bank->id, 'tipe' => 'setoran', 'nilai' => $nilai, 'saldo_setelah' => $nilai, 'tanggal' => now()->toDateString()]);
    }

    public function test_penangguhan_menunda_setoran_dan_menyembunyikan_bank_lalu_dipulihkan(): void
    {
        [$bank, $supplier, $customer] = $this->bankDenganSupplier();
        $setoran = $this->setoran($bank, $customer);
        $admin = $this->admin('Pengelola Akun');
        $service = app(PenghentianService::class);

        $this->assertSame(1, BankSampah::tampil()->count());

        $service->ubah($admin, $supplier, 'ditangguhkan', 'Pemeriksaan data');
        $this->assertSame('ditunda', $setoran->fresh()->status);
        $this->assertSame('diajukan', $setoran->fresh()->status_sebelum_ditunda);
        $this->assertSame(0, BankSampah::tampil()->count());

        $service->ubah($admin, $supplier, 'aktif', 'Data valid');
        $this->assertSame('diajukan', $setoran->fresh()->status);
        $this->assertSame(1, BankSampah::tampil()->count());
    }

    public function test_dalam_penyelesaian_membatalkan_setoran_belum_diterima_dan_memberi_tahu_customer(): void
    {
        [$bank, $supplier, $customer] = $this->bankDenganSupplier();
        $belum = $this->setoran($bank, $customer);
        $diterima = $this->setoran($bank, $customer, 'diterima');
        $this->saldo($customer, $bank, 15000);

        app(PenghentianService::class)->ubah($this->admin('Pengelola Akun'), $supplier, 'dalam_penyelesaian', 'Bank tutup');

        $this->assertSame('dibatalkan', $belum->fresh()->status);
        $this->assertSame('sistem', $belum->fresh()->dibatalkan_oleh);
        $this->assertSame('diterima', $diterima->fresh()->status);                 // tetap, boleh ditimbang
        $this->assertNotNull($supplier->fresh()->batas_penyelesaian);
        $this->assertDatabaseHas('log_email', ['jenis' => 'bank_dihentikan', 'penerima' => $customer->email]);
        $this->assertEquals(15000.0, $customer->saldo($bank->id));                // saldo tidak hilang
    }

    public function test_nonaktif_saat_ada_saldo_hanya_untuk_pemegang_izin_khusus_dengan_rencana(): void
    {
        [$bank, $supplier, $customer] = $this->bankDenganSupplier();
        $this->saldo($customer, $bank, 10000);
        $service = app(PenghentianService::class);

        try {
            $service->ubah($this->admin('Pengelola Akun'), $supplier, 'nonaktif', 'Tutup');
            $this->fail('Harus ditolak.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $khusus = $this->admin('Penanganan Penutupan Bank Sampah');
        try {
            $service->ubah($khusus, $supplier, 'nonaktif', 'Tutup', null);
            $this->fail('Rencana wajib.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rencana', $e->errors());
        }

        $service->ubah($khusus, $supplier, 'nonaktif', 'Tutup', 'Saldo dicairkan tunai oleh pengurus');
        $this->assertSame('nonaktif', $supplier->fresh()->status_akun);
    }

    public function test_supplier_dalam_penyelesaian_hanya_boleh_fitur_terbatas(): void
    {
        [$bank, $supplier] = $this->bankDenganSupplier();
        $supplier->update(['status_akun' => 'dalam_penyelesaian', 'batas_penyelesaian' => now()->addDays(10)]);

        $this->actingAs($supplier)->get(route('supplier.pencairan.index'))->assertOk();
        $this->actingAs($supplier)->get(route('supplier.laporan.index'))->assertOk();
        $this->actingAs($supplier)->get(route('supplier.jenis.index'))->assertForbidden();
        $this->actingAs($supplier)->get(route('supplier.pengolahan.index'))->assertForbidden();
    }

    public function test_status_ditangguhkan_menghentikan_sesi_yang_berjalan(): void
    {
        [, $supplier] = $this->bankDenganSupplier();
        $supplier->update(['status_akun' => 'ditangguhkan']);

        $this->actingAs($supplier)->get(route('supplier.beranda'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_akun_ditangguhkan_ditolak_dengan_pesan_status_setelah_sandi_benar(): void
    {
        [, $supplier] = $this->bankDenganSupplier();
        $supplier->update(['status_akun' => 'ditangguhkan']);

        $this->post(route('login'), ['email' => $supplier->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_setoran_baru_ke_bank_tidak_aktif_ditolak(): void
    {
        [$bank, $supplier, $customer] = $this->bankDenganSupplier();
        $supplier->update(['status_akun' => 'ditangguhkan']);

        $this->actingAs($customer)->get(route('customer.setoran.create', ['bank' => $bank->id]))->assertRedirect(route('customer.bank.index'));
    }

    public function test_saldo_dicatat_per_bank_sampah(): void
    {
        [$bankA, , $customer] = $this->bankDenganSupplier();
        [$bankB] = $this->bankDenganSupplier();
        $this->saldo($customer, $bankA, 5000);
        $this->saldo($customer, $bankB, 7000);

        $this->assertEquals(5000.0, $customer->saldo($bankA->id));
        $this->assertEquals(7000.0, $customer->saldo($bankB->id));
        $this->assertEquals(12000.0, $customer->saldo());
    }

    // ------------------------------------------------------------ manajemen akun Admin

    public function test_super_admin_terakhir_tidak_bisa_dinonaktifkan(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->post(route('superadmin.admin.status', $super), ['status' => 'nonaktif', 'alasan' => 'tes', 'konfirmasi_sandi' => 'password'])
            ->assertSessionHasErrors('status');
        $this->assertSame('aktif', $super->fresh()->status_akun);
    }

    public function test_membuat_super_admin_memerlukan_konfirmasi_kata_sandi(): void
    {
        $super = User::factory()->superAdmin()->create();
        $data = ['nama' => 'Super Baru', 'email' => 'baru@example.test', 'nomor_hp' => '081234567891', 'jenis' => 'super_admin'];

        $this->actingAs($super)->post(route('superadmin.admin.store'), $data + ['konfirmasi_sandi' => 'salah'])->assertSessionHasErrors('konfirmasi_sandi');
        $this->actingAs($super)->post(route('superadmin.admin.store'), $data + ['konfirmasi_sandi' => 'password'])->assertRedirect(route('superadmin.admin.index'));

        $baru = User::where('email', 'baru@example.test')->first();
        $this->assertSame('menunggu_aktivasi', $baru->status_akun);
        $this->assertNull($baru->password);
        $this->assertDatabaseHas('log_email', ['jenis' => 'undangan_admin', 'penerima' => 'baru@example.test']);
    }

    public function test_role_sistem_tidak_bisa_diubah_dan_role_terpakai_tidak_bisa_dihapus(): void
    {
        $super = User::factory()->superAdmin()->create();
        $sistem = Role::where('sistem', true)->first();
        $this->actingAs($super)->get(route('superadmin.role.edit', $sistem))->assertForbidden();

        $admin = $this->admin('Pemantau');
        $this->actingAs($super)->delete(route('superadmin.role.destroy', Role::where('nama', 'Pemantau')->first()))->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['nama' => 'Pemantau']);
    }

    public function test_hak_akses_khusus_super_admin_tidak_bisa_diberikan_lewat_role(): void
    {
        $super = User::factory()->superAdmin()->create();

        $this->actingAs($super)->post(route('superadmin.role.store'), ['nama' => 'Nakal', 'aktif' => true, 'izin' => ['role.kelola', 'pengajuan.lihat']]);

        $kode = Role::where('nama', 'Nakal')->first()->permissions()->pluck('kode')->all();
        $this->assertSame(['pengajuan.lihat'], $kode);
    }

    public function test_log_audit_tidak_dapat_diubah_atau_dihapus(): void
    {
        \App\Services\AuditLogger::catat('uji', 1, 'aksi');
        $log = \App\Models\LogAudit::first();

        $log->update(['aksi' => 'diubah']);
        $this->assertSame('aksi', $log->fresh()->aksi);
        $log->delete();
        $this->assertDatabaseCount('log_audit', 1);
    }

    public function test_admin_tanpa_hak_kontak_melihat_kontak_customer_disamarkan(): void
    {
        $customer = User::factory()->create(['email' => 'rahasia@example.test', 'nomor_hp' => '081234567890']);
        $admin = $this->admin('Pengelola Akun');

        $this->actingAs($admin)->get(route('admin.customer.index'))->assertOk()->assertDontSee('rahasia@example.test')->assertDontSee('081234567890');
    }
}
