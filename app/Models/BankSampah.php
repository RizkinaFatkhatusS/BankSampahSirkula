<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class BankSampah extends Model
{
    use HasFactory;

    protected $table = 'bank_sampah';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    public function jenisSampah(): HasMany
    {
        return $this->hasMany(JenisSampah::class);
    }

    public function supplier(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'supplier');
    }

    public function setoran(): HasMany
    {
        return $this->hasMany(Setoran::class);
    }

    public function mitra(): HasMany
    {
        return $this->hasMany(Mitra::class);
    }

    /**
     * Bank sampah yang tampil pada pencarian Customer (FR-37, FR-39, FR-41):
     * akun Supplier Aktif, status tayang Tayang, dan punya jenis sampah aktif dengan harga.
     */
    public function scopeTampil($query)
    {
        return $query->where('status_tayang', 'tayang')
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('users')
                ->whereColumn('users.bank_sampah_id', 'bank_sampah.id')
                ->where('users.role', 'supplier')->where('users.status_akun', 'aktif'))
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('jenis_sampah')
                ->whereColumn('jenis_sampah.bank_sampah_id', 'bank_sampah.id')
                ->where('jenis_sampah.aktif', true)->where('jenis_sampah.harga_per_kg', '>', 0));
    }

    public function fotoUrl(): ?string
    {
        return $this->foto ? Storage::url($this->foto) : null;
    }

    /** Jarak Haversine dalam km (dihitung di sisi server dari koordinat). */
    public static function jarakKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)), 1);
    }
}
