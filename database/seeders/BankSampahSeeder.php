<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BankSampah;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BankSampahSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat Data Bank Sampah Utama
        $bankSampah = BankSampah::create([
            'nama' => 'Bank Sampah 3R Sukajadi',
            'alamat' => 'Jl. Sukajadi Indah No. 45, RT 03/RW 04, Bandung',
            'latitude' => -6.91750000,
            'longitude' => 107.61910000,
            'nomor_kontak' => '081234567890',
            'jam_operasional' => '08:00 - 16:00 WIB',
        ]);

        // 2. Buat Akun Supplier / Petugas
        User::create([
            'nama' => 'Pak Budi Santoso',
            'email' => 'supplier@sirkula.test',
            'nomor_hp' => '081211112222',
            'password' => Hash::make('password'),
            'role' => 'supplier',
            'bank_sampah_id' => $bankSampah->id,
        ]);

        // 3. Buat Akun Customer / Warga
        User::create([
            'nama' => 'Siti Aminah',
            'email' => 'customer@sirkula.test',
            'nomor_hp' => '081233334444',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
    }
}
