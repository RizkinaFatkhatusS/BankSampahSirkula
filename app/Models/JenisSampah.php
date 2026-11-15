<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JenisSampah extends Model
{
    protected $table = 'jenis_sampah';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['harga_per_kg' => 'decimal:2', 'berlaku_sejak' => 'date', 'aktif' => 'boolean'];
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class);
    }
}
