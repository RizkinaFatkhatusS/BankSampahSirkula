<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjualanItem extends Model
{
    protected $table = 'penjualan_item';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['jumlah' => 'decimal:2', 'harga_jual' => 'decimal:2'];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(ProdukOlahan::class, 'produk_olahan_id');
    }
}
