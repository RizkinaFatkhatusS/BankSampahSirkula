<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiSaldo extends Model
{
    protected $table = 'mutasi_saldo';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['nilai' => 'decimal:2', 'saldo_setelah' => 'decimal:2', 'tanggal' => 'date'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class, 'bank_sampah_id');
    }

    public function setoran(): BelongsTo
    {
        return $this->belongsTo(Setoran::class);
    }
}
