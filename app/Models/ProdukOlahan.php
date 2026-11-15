<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProdukOlahan extends Model
{
    protected $table = 'produk_olahan';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['jumlah' => 'decimal:2', 'harga_jual' => 'decimal:2'];
    }

    public function pengolahan(): BelongsTo
    {
        return $this->belongsTo(Pengolahan::class);
    }

    public function fotoUrl(): ?string
    {
        return $this->foto ? Storage::url($this->foto) : null;
    }
}
