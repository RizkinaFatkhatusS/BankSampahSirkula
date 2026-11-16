<?php

namespace App\Services;

use App\Models\LogAudit;

class AuditLogger
{
    public static function catat(string $entitas, int $entitasId, string $aksi, mixed $sebelum = null, mixed $sesudah = null, ?string $alasan = null): void
    {
        LogAudit::create([
            'entitas' => $entitas,
            'entitas_id' => $entitasId,
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'nilai_sebelum' => $sebelum === null ? null : json_encode($sebelum, JSON_UNESCAPED_UNICODE),
            'nilai_sesudah' => $sesudah === null ? null : json_encode($sesudah, JSON_UNESCAPED_UNICODE),
            'alasan' => $alasan,
            'ip_address' => request()?->ip(),
        ]);
    }
}
