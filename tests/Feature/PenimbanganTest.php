<?php

namespace Tests\Feature;

use App\Models\BankSampah;
use App\Models\JenisSampah;
use App\Models\Setoran;
use App\Models\User;
use App\Services\SetoranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenimbanganTest extends TestCase
{
    use RefreshDatabase;

    private function siapkan(float $harga = 2000): array
    {
        $bank = BankSampah::create(['nama' => 'Bank A', 'alamat' => 'Jl. A', 'latitude' => -6.4, 'longitude' => 106.8, 'nomor_kontak' => '0812', 'jam_operasional' => '08-16']);
        $supplier = User::factory()->supplier($bank->id)->create();
        $customer = User::factory()->create();
        $plastik = JenisSampah::create(['bank_sampah_id' => $bank->id, 'nama' => 'Plastik', 'kategori' => 'anorganik', 'harga_per_kg' => $harga]);
        $setoran = Setoran::create(['customer_id' => $customer->id, 'bank_sampah_id' => $bank->id, 'foto_bukti' => 'x.png', 'estimasi_nilai' => $harga, 'metode_penyerahan' => 'antar_sendiri', 'status' => 'diterima']);
        $item = $setoran->items()->create(['jenis_sampah_id' => $plastik->id]);

        return [$bank, $supplier, $customer, $plastik, $setoran, $item];
    }

    public function test_nilai_final_dihitung_dari_berat_aktual_dan_menambah_saldo(): void
    {
        [, $supplier, $customer, , $setoran, $item] = $this->siapkan(2000);

        // PRD FR-19: 2,5 kg x Rp 2.000 = Rp 5.000
        app(SetoranService::class)->timbang($setoran, $supplier, [$item->id => 2.5]);

        $this->assertSame('5000.00', $setoran->fresh()->nilai_final);
        $this->assertSame('selesai', $setoran->fresh()->status);
        $this->assertEquals(5000.0, $customer->saldo());
        $this->assertDatabaseHas('mutasi_saldo', ['customer_id' => $customer->id, 'tipe' => 'setoran', 'nilai' => 5000, 'saldo_setelah' => 5000]);
        $this->assertCount(1, $customer->notifications);
    }

    public function test_harga_yang_dipakai_adalah_harga_saat_penimbangan(): void
    {
        [, $supplier, $customer, $plastik, $setoran, $item] = $this->siapkan(2000);
        $plastik->update(['harga_per_kg' => 3000]); // harga berubah setelah setoran diajukan

        app(SetoranService::class)->timbang($setoran, $supplier, [$item->id => 2]);

        $this->assertEquals(6000.0, $customer->saldo());
    }

    public function test_pencairan_mengurangi_saldo_dan_tidak_boleh_melebihi_saldo(): void
    {
        [, $supplier, $customer, , $setoran, $item] = $this->siapkan(10000);
        $service = app(SetoranService::class);
        $service->timbang($setoran, $supplier, [$item->id => 2]); // saldo 20.000

        $service->cairkan($supplier, $customer, 10000, 'Tunai di bank sampah');
        $this->assertEquals(10000.0, $customer->saldo());

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->cairkan($supplier, $customer, 50000, 'Tunai di bank sampah');
    }

    public function test_setoran_tidak_bisa_ditimbang_dua_kali(): void
    {
        [, $supplier, , , $setoran, $item] = $this->siapkan();
        $service = app(SetoranService::class);
        $service->timbang($setoran, $supplier, [$item->id => 1]);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->timbang($setoran->fresh(), $supplier, [$item->id => 1]);
    }

    public function test_supplier_lain_tidak_boleh_mengakses_setoran_bank_lain(): void
    {
        [, , , , $setoran] = $this->siapkan();
        $bankLain = BankSampah::create(['nama' => 'Bank B', 'alamat' => 'Jl. B', 'latitude' => -6.5, 'longitude' => 106.9, 'nomor_kontak' => '0813', 'jam_operasional' => '08-16']);
        $supplierLain = User::factory()->supplier($bankLain->id)->create();

        $this->actingAs($supplierLain)->get(route('supplier.setoran.show', $setoran))->assertForbidden();
    }

    public function test_customer_tidak_bisa_membuka_halaman_supplier_dan_sebaliknya(): void
    {
        [, $supplier, $customer] = $this->siapkan();

        $this->actingAs($customer)->get(route('supplier.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('supplier.laporan.index'))->assertForbidden();
        $this->actingAs($supplier)->get(route('customer.beranda'))->assertForbidden();
    }

    public function test_dashboard_mengarahkan_sesuai_role(): void
    {
        [, $supplier, $customer] = $this->siapkan();

        $this->actingAs($customer)->get('/dashboard')->assertRedirect(route('customer.beranda'));
        $this->actingAs($supplier)->get('/dashboard')->assertRedirect(route('supplier.beranda'));
    }

    public function test_setoran_wajib_memakai_foto(): void
    {
        [$bank, , $customer, $plastik] = $this->siapkan();

        $this->actingAs($customer)->post(route('customer.setoran.store'), [
            'bank_sampah_id' => $bank->id,
            'jenis_sampah_ids' => [$plastik->id],
            'metode_penyerahan' => 'antar_sendiri',
        ])->assertSessionHasErrors('foto_bukti');
    }
}
