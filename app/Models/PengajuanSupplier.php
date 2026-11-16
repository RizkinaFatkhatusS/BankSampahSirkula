<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanSupplier extends Model
{
    protected $table = 'pengajuan_supplier';

    protected $guarded = [];

    public const STATUS_AKTIF = ['menunggu_verifikasi', 'sedang_diverifikasi', 'perlu_revisi'];

    public const LABEL = [
        'menunggu_verifikasi' => 'Menunggu verifikasi',
        'sedang_diverifikasi' => 'Sedang diverifikasi',
        'perlu_revisi' => 'Perlu revisi',
        'disetujui' => 'Disetujui',
        'ditolak' => 'Ditolak',
        'kedaluwarsa' => 'Kedaluwarsa',
    ];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float', 'diputuskan_pada' => 'datetime', 'pengingat_terakhir_at' => 'datetime'];
    }

    public function dokumen(): HasMany
    {
        return $this->hasMany(DokumenPengajuan::class, 'pengajuan_id');
    }

    public function riwayat(): HasMany
    {
        return $this->hasMany(RiwayatStatusPengajuan::class, 'pengajuan_id')->orderBy('id');
    }

    public function penangan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditangani_oleh');
    }

    public function pemutus(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diputuskan_oleh');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh_admin_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class);
    }

    public function tautanStatus(): string
    {
        return route('pengajuan.status', $this->token_status);
    }

    public function labelStatus(): string
    {
        return self::LABEL[$this->status] ?? $this->status;
    }
}
