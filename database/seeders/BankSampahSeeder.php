<?php

namespace Database\Seeders;

use App\Models\BankSampah;
use App\Models\JenisSampah;
use App\Models\Mitra;
use App\Models\MutasiSaldo;
use App\Models\Pengolahan;
use App\Models\Setoran;
use App\Models\User;
use App\Services\SetoranService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Data contoh. Harga hanya contoh gambaran (PRD 0: harga sebenarnya belum ditetapkan).
 *
 * Akun (kata sandi semua: password)
 *   supplier@sirkula.test  -> Bank Sampah Sukamaju (punya data lengkap)
 *   supplier2@sirkula.test -> Bank Sampah Melati Asri
 *   supplier3@sirkula.test -> Bank Sampah Hijau Lestari
 *   customer@sirkula.test, budi@sirkula.test -> Customer
 */
class BankSampahSeeder extends Seeder
{
    public function run(): void
    {
        // Gambar contoh agar foto bukti tampil tanpa unggahan nyata
        Storage::disk('public')->put('setoran/contoh.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="400" height="300" fill="#dcfce7"/><text x="200" y="150" font-size="22" text-anchor="middle" fill="#166534" font-family="sans-serif">Foto sampah contoh</text></svg>');

        $banks = [
            ['Bank Sampah Sukamaju', 'Jl. Melati No. 12, Beji, Depok', -6.3745, 106.8227, '081234567890', '08.00 – 16.00 WIB', 'supplier@sirkula.test', 'Pak Budi Santoso'],
            ['Bank Sampah Melati Asri', 'Jl. Margonda Raya No. 88, Depok', -6.3690, 106.8330, '081355577799', '09.00 – 15.00 WIB', 'supplier2@sirkula.test', 'Bu Ratna Dewi'],
            ['Bank Sampah Hijau Lestari', 'Jl. Raya Sawangan No. 5, Pancoran Mas, Depok', -6.4090, 106.7950, '081399900011', '08.00 – 17.00 WIB', 'supplier3@sirkula.test', 'Pak Agus Salim'],
        ];

        $jenisAwal = [
            ['Plastik', '🧴', 'anorganik', 2000],
            ['Kertas & Karton', '📄', 'anorganik', 1500],
            ['Organik', '🍃', 'organik', 500],
            ['Logam', '🔩', 'anorganik', 3000],
        ];

        foreach ($banks as $i => [$nama, $alamat, $lat, $lng, $hp, $jam, $email, $pj]) {
       $bank = BankSampah::updateOrCreate(
    ['nama' => $nama],
    [
        'alamat' => $alamat,
        'latitude' => $lat,
        'longitude' => $lng,
        'nomor_kontak' => $hp,
        'jam_operasional' => $jam,
    ]
);
        // $bank = BankSampah::create(['nama' => $nama, 'alamat' => $alamat, 'latitude' => $lat, 'longitude' => $lng, 'nomor_kontak' => $hp, 'jam_operasional' => $jam]);

            User::updateOrCreate(
    ['email' => $email],
    [
        'nama' => $pj,
        'nomor_hp' => $hp,
        'password' => 'password',
        'role' => 'supplier',
        'bank_sampah_id' => $bank->id,
    ]
);

            foreach ($jenisAwal as [$n, $ikon, $kat, $harga]) {
                // bank kedua tidak menerima organik, supaya filter Organik terlihat bedanya
                if ($i === 1 && $kat === 'organik') {
                    continue;
                }
               // JenisSampah::create(['bank_sampah_id' => $bank->id, 'nama' => $n, 'ikon' => $ikon, 'kategori' => $kat, 'harga_per_kg' => $harga + $i * 100, 'berlaku_sejak' => now()->toDateString()]);
           JenisSampah::updateOrCreate(
    [
        'bank_sampah_id' => $bank->id,
        'nama' => $n,
    ],
    [
        'ikon' => $ikon,
        'kategori' => $kat,
        'harga_per_kg' => $harga + $i * 100,
        'berlaku_sejak' => now()->toDateString(),
    ]
);
               }
        }
$siti = User::updateOrCreate(
    ['email' => 'customer@sirkula.test'],
    [
        'nama' => 'Siti Aminah',
        'nomor_hp' => '081233334444',
        'password' => 'password',
        'role' => 'customer',
    ]
);

$budi = User::updateOrCreate(
    ['email' => 'budi@sirkula.test'],
    [
        'nama' => 'Budi Hartono',
        'nomor_hp' => '081255556666',
        'password' => 'password',
        'role' => 'customer',
    ]
);
        // $siti = User::create(['nama' => 'Siti Aminah', 'email' => 'customer@sirkula.test', 'nomor_hp' => '081233334444', 'password' => 'password', 'role' => 'customer']);
        // $budi = User::create(['nama' => 'Budi Hartono', 'email' => 'budi@sirkula.test', 'nomor_hp' => '081255556666', 'password' => 'password', 'role' => 'customer']);

        $this->dataContoh(BankSampah::first(), User::where('email', 'supplier@sirkula.test')->first(), $siti, $budi);
    }

    private function dataContoh(BankSampah $bank, User $supplier, User $siti, User $budi): void
    {
        $service = app(SetoranService::class);
        $jenis = $bank->jenisSampah->keyBy('nama');

        // [customer, [jenis => berat], metode, status akhir]
        $rencana = [
            [$siti, ['Kertas & Karton' => 1.5], 'antar_sendiri', now()->subDays(20)],
            [$siti, ['Plastik' => 2.0, 'Logam' => 0.8], 'dijemput_kurir', now()->subDays(9)],
            [$budi, ['Organik' => 6.0, 'Plastik' => 1.2], 'antar_sendiri', now()->subDays(6)],
            [$budi, ['Kertas & Karton' => 3.0], 'dijemput_kurir', now()->subDays(2)],
        ];

        foreach ($rencana as [$cust, $berat, $metode, $waktu]) {
            $setoran = $this->buatSetoran($cust, $bank, $jenis, array_keys($berat), $metode, 'diterima', $waktu);
            $beratPerItem = [];
            foreach ($setoran->items as $item) {
                $beratPerItem[$item->id] = $berat[$item->jenisSampah->nama];
            }
            Auth::setUser($supplier);
            $service->timbang($setoran, $supplier, $beratPerItem);
            $selesai = $waktu->copy()->addHours(3);
            $setoran->forceFill(['created_at' => $waktu, 'ditimbang_pada' => $selesai])->save();
            $setoran->items()->update(['ditimbang_pada' => $selesai]);
            MutasiSaldo::where('setoran_id', $setoran->id)->update(['tanggal' => $selesai->toDateString()]);
        }

        // Setoran yang masih berjalan agar beranda Supplier tidak kosong
        $this->buatSetoran($siti, $bank, $jenis, ['Plastik'], 'dijemput_kurir', 'menunggu_penjemputan', now()->subHours(5));
        $this->buatSetoran($budi, $bank, $jenis, ['Logam', 'Kertas & Karton'], 'antar_sendiri', 'diajukan', now()->subHour());

        // Mitra contoh + satu pengolahan
        Mitra::create(['bank_sampah_id' => $bank->id, 'nama' => 'CV Tani Subur', 'kontak' => '081377788899']);
        Mitra::create(['bank_sampah_id' => $bank->id, 'nama' => 'Toko Kerajinan Kriya Nusa', 'kontak' => '081311122233']);

        $p = Pengolahan::create(['bank_sampah_id' => $bank->id, 'kategori_sampah' => 'organik', 'jenis_olahan' => 'pupuk_cair', 'berat_diolah' => 6, 'tanggal' => now()->subDays(4)->toDateString(), 'catatan' => 'Fermentasi 7 hari', 'foto_dokumentasi' => 'setoran/contoh.svg']);
        $p->produk()->create(['bank_sampah_id' => $bank->id, 'nama_produk' => 'Pupuk cair organik 1 L', 'kategori' => 'pupuk_cair', 'jumlah' => 12, 'satuan' => 'botol', 'harga_jual' => 15000]);
    }

    private function buatSetoran(User $cust, BankSampah $bank, $jenis, array $namaJenis, string $metode, string $status, $waktu): Setoran
    {
        $dipilih = collect($namaJenis)->map(fn ($n) => $jenis[$n]);

        $s = Setoran::create([
            'customer_id' => $cust->id,
            'bank_sampah_id' => $bank->id,
            'foto_bukti' => 'setoran/contoh.svg',
            'estimasi_nilai' => $dipilih->sum('harga_per_kg'),
            'metode_penyerahan' => $metode,
            'alamat_jemput' => $metode === 'dijemput_kurir' ? 'Jl. Kenanga No. 7, Depok' : null,
            'jadwal_jemput' => $metode === 'dijemput_kurir' ? '09.00 – 11.00' : null,
            'status' => $status,
        ]);
        foreach ($dipilih as $j) {
            $s->items()->create(['jenis_sampah_id' => $j->id]);
        }
        $s->forceFill(['created_at' => $waktu, 'updated_at' => $waktu])->save();

        return $s->load('items.jenisSampah');
    }
}
