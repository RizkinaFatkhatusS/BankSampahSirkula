<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAkses extends Model
{
    protected $table = 'log_akses';

    public $timestamps = false;

    protected $guarded = [];

    /** Catat kejadian akses. Tidak pernah melempar galat agar tidak mengganggu alur utama. */
    public static function catat(string $jenis, array $detail = [], ?int $userId = null): void
    {
        try {
            $request = request();
            static::create([
                'user_id' => $userId ?? auth()->id(),
                'jenis' => $jenis,
                'rute' => $request?->route()?->getName() ?? $request?->path(),
                'ip_address' => $request?->ip(),
                'detail' => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (\Throwable) {
            // abaikan
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
