<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_SUPPLIER = 'supplier';

    protected $fillable = ['nama', 'email', 'nomor_hp', 'password', 'role', 'bank_sampah_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Alias agar kode bawaan Breeze yang memakai "name" tetap jalan. */
    public function getNameAttribute(): string
    {
        return (string) $this->nama;
    }

    public function isSupplier(): bool
    {
        return $this->role === self::ROLE_SUPPLIER;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }

    public function bankSampah(): BelongsTo
    {
        return $this->belongsTo(BankSampah::class);
    }

    public function setoran(): HasMany
    {
        return $this->hasMany(Setoran::class, 'customer_id');
    }

    public function mutasiSaldo(): HasMany
    {
        return $this->hasMany(MutasiSaldo::class, 'customer_id');
    }

    /**
     * Saldo hanya berasal dari mutasi_saldo (PRD 5.4): saldo = saldo_setelah pada mutasi terakhir.
     */
    public function saldo(): float
    {
        $last = $this->mutasiSaldo()->orderByDesc('id')->first();

        return $last ? (float) $last->saldo_setelah : 0.0;
    }
}
