<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Log audit bersifat tambah-saja: tidak dapat diubah atau dihapus melalui aplikasi (FR-45). */
class LogAudit extends Model
{
    protected $table = 'log_audit';

    public $timestamps = false;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updating(fn () => false);
        static::deleting(fn () => false);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
