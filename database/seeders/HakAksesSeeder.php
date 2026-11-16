<?php

namespace Database\Seeders;

use App\Models\JenisDokumen;
use App\Models\MasterKategori;
use App\Models\Permission;
use App\Models\Role;
use App\Support\HakAksesKatalog;
use Illuminate\Database\Seeder;

/**
 * Katalog hak akses tetap, role bawaan, serta data master awal.
 * Aman dijalankan berulang (tidak menimpa role/master yang sudah diubah Super Admin):
 *   php artisan db:seed --class=HakAksesSeeder
 */
class HakAksesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (HakAksesKatalog::semua() as [$kode, $kelompok, $deskripsi, $jenis]) {
            Permission::updateOrCreate(['kode' => $kode], ['kelompok' => $kelompok, 'deskripsi' => $deskripsi, 'jenis' => $jenis]);
        }

        // Role sistem: Super Admin selalu memiliki seluruh hak akses dan tidak dapat diubah/dihapus
        Role::firstOrCreate(['nama' => 'Super Admin'], ['deskripsi' => 'Memiliki seluruh hak akses (role sistem)', 'aktif' => true, 'sistem' => true]);

        foreach (HakAksesKatalog::roleBawaan() as $nama => [$deskripsi, $kode]) {
            $role = Role::firstOrCreate(['nama' => $nama], ['deskripsi' => $deskripsi, 'aktif' => true, 'sistem' => false]);
            if ($role->wasRecentlyCreated) {
                $role->permissions()->sync(Permission::whereIn('kode', $kode)->pluck('id'));
            }
        }

        // Persyaratan dokumen pendaftaran Supplier (usulan PRD FR-35)
        $dokumen = [
            ['Identitas penanggung jawab', 'KTP penanggung jawab bank sampah; foto jelas dan terbaca', true, 1],
            ['Dokumen legalitas atau pengesahan bank sampah', 'SK pembentukan dari kelurahan atau desa, rekomendasi dinas lingkungan hidup, atau NIB, sesuai yang dimiliki', true, 1],
            ['Foto lokasi dan fasilitas', 'Tampak depan dan area penimbangan (minimal 2 foto)', true, 2],
            ['Surat pernyataan', 'Pernyataan kebenaran data dan kesediaan menggunakan sistem; formatnya disediakan sistem (tombol unduh pada formulir)', true, 1],
            ['Dokumen pendukung lain', 'Misalnya NPWP, struktur pengurus, atau bukti kerja sama dengan pihak lain', false, 1],
        ];
        foreach ($dokumen as $i => [$nama, $ket, $wajib, $min]) {
            JenisDokumen::firstOrCreate(['nama' => $nama], ['keterangan' => $ket, 'wajib' => $wajib, 'aktif' => true, 'urutan' => $i + 1, 'min_berkas' => $min]);
        }

        $master = [
            'kategori_sampah' => ['organik' => 'Organik', 'anorganik' => 'Anorganik'],
            'kategori_olahan' => ['pupuk_cair' => 'Pupuk cair', 'kerajinan' => 'Kerajinan', 'energi_lain' => 'Energi lain'],
            'satuan' => ['botol' => 'botol', 'buah' => 'buah', 'kg' => 'kg', 'liter' => 'liter', 'pak' => 'pak', 'pcs' => 'pcs'],
        ];
        foreach ($master as $tipe => $item) {
            foreach ($item as $kode => $nama) {
                MasterKategori::firstOrCreate(['tipe' => $tipe, 'kode' => $kode], ['nama' => $nama, 'aktif' => true]);
            }
        }
    }
}
