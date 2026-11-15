<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetoranItem extends Model
{
    protected $table = 'setoran_item';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'harga_per_kg' => 'decimal:2',
            'berat_aktual' => 'decimal:2',
            'nilai_final' => 'decimal:2',
            'ditimbang_pada' => 'datetime',
        ];
    }

    public function setoran(): BelongsTo
    {
        return $this->belongsTo(Setoran::class);
    }

    public function jenisSampah(): BelongsTo
    {
        return $this->belongsTo(JenisSampah::class);
    }
}
