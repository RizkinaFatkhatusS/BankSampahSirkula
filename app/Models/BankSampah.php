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
