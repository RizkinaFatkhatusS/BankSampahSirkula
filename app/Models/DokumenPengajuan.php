<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenPengajuan extends Model
{
    protected $table = 'dokumen_pengajuan';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['diunggah_pada' => 'datetime'];
    }

    public function jenis(): BelongsTo
    {
        return $this->belongsTo(JenisDokumen::class, 'jenis_dokumen_id');
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanSupplier::class, 'pengajuan_id');
    }
}
