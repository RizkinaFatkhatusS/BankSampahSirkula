<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterKategori extends Model
{
    protected $table = 'master_kategori';

    protected $guarded = [];

    public const TIPE = [
        'kategori_sampah' => 'Kategori sampah',
        'kategori_olahan' => 'Kategori hasil olahan',
        'satuan' => 'Satuan produk',
    ];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    /** Nilai awal bila data master belum diisi (mis. seeder belum dijalankan). */
    private const BAWAAN = [
        'kategori_sampah' => ['anorganik' => 'Anorganik', 'organik' => 'Organik'],
        'kategori_olahan' => ['energi_lain' => 'Energi lain', 'kerajinan' => 'Kerajinan', 'pupuk_cair' => 'Pupuk cair'],
        'satuan' => ['botol' => 'botol', 'buah' => 'buah', 'kg' => 'kg', 'liter' => 'liter', 'pak' => 'pak', 'pcs' => 'pcs'],
    ];

    /** Daftar [kode => nama] untuk satu tipe (hanya yang aktif kecuali $semua). */
    public static function daftar(string $tipe, bool $semua = false): array
    {
        $hasil = static::where('tipe', $tipe)->when(! $semua, fn ($q) => $q->where('aktif', true))
            ->orderBy('nama')->pluck('nama', 'kode')->all();

        return $hasil ?: (static::where('tipe', $tipe)->exists() ? [] : (self::BAWAAN[$tipe] ?? []));
    }
}
