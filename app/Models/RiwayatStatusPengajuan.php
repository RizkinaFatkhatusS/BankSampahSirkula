<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatStatusPengajuan extends Model
{
    protected $table = 'riwayat_status_pengajuan';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'oleh');
    }
}
