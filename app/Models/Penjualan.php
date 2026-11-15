<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Penjualan extends Model
{
    protected $table = 'penjualan';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['nilai_transaksi' => 'decimal:2', 'tanggal' => 'date'];
    }

    public function mitra(): BelongsTo
    {
        return $this->belongsTo(Mitra::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PenjualanItem::class);
    }

    public function buktiUrl(): ?string
    {
        return $this->bukti_pembayaran ? Storage::url($this->bukti_pembayaran) : null;
    }
}
