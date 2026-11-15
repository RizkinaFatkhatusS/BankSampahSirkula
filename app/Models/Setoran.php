<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Setoran extends Model
{
    protected $table = 'setoran';

    protected $guarded = [];

    /** Status yang masih boleh dibatalkan / ditolak (sebelum sampah diterima) */
    public const STATUS_SEBELUM_DITERIMA = ['diajukan', 'disetujui', 'menunggu_penjemputan', 'dalam_penjemputan'];

    protected function casts(): array
    {
        return [
            'estimasi_nilai' => 'decimal:2',
            'total_berat' => 'decimal:2',
            'nilai_final' => 'decimal:2',
            'ditimbang_pada' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SetoranItem::class);
    }

    public function riwayatPenjemputan(): HasMany
    {
        return $this->hasMany(StatusPenjemputan::class)->orderBy('id');
    }

    public function fotoUrl(): string
    {
        return Storage::url($this->foto_bukti);
    }

    public function bisaDibatalkan(): bool
    {
        return in_array($this->status, self::STATUS_SEBELUM_DITERIMA, true);
    }
}
