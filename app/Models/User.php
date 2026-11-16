<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_SUPPLIER = 'supplier';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    protected $fillable = [
        'nama', 'email', 'nomor_hp', 'password', 'role', 'bank_sampah_id',
        'status_akun', 'alasan_status', 'terakhir_login', 'batas_penyelesaian', 'mfa_aktif', 'mfa_secret',
    ];

    protected $hidden = ['password', 'remember_token', 'mfa_secret'];

    private ?array $izinMemo = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'terakhir_login' => 'datetime',
            'batas_penyelesaian' => 'datetime',
            'mfa_aktif' => 'boolean',
            'mfa_secret' => 'encrypted',
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

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }

    /** Admin atau Super Admin (pengelola platform). */
    public function isStaf(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'model_has_roles', 'user_id', 'role_id');
    }

    // ------------------------------------------------------------- hak akses

    /** Kode hak akses efektif: gabungan seluruh role aktif. Super Admin memiliki semuanya. */
    public function izinEfektif(): array
    {
        if ($this->izinMemo !== null) {
            return $this->izinMemo;
        }

        if ($this->isSuperAdmin()) {
            return $this->izinMemo = Permission::pluck('kode')->all();
        }
        if (! $this->isAdmin() || $this->status_akun !== 'aktif') {
            return $this->izinMemo = [];
        }

        // Dihitung dari database pada setiap permintaan, jadi perubahan role berlaku segera (FR-49)
        return $this->izinMemo = DB::table('permissions')
            ->join('role_has_permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->join('model_has_roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.user_id', $this->id)
            ->where('roles.aktif', true)
            ->where('permissions.jenis', '!=', Permission::KHUSUS_SUPER_ADMIN)
            ->distinct()->pluck('permissions.kode')->all();
    }

    public function punya(string $kode): bool
    {
        return in_array($kode, $this->izinEfektif(), true);
    }

    public function punyaIzinSensitif(): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        return Permission::whereIn('kode', $this->izinEfektif())->where('jenis', Permission::SENSITIF)->exists();
    }

    // ------------------------------------------------------------- 2FA

    /** Super Admin selalu, Admin bila memegang hak akses sensitif (usulan PRD). */
    public function wajibMfa(): bool
    {
        if (! config('sirkula.mfa_wajib')) {
            return false;
        }

        return $this->isSuperAdmin() || $this->punyaIzinSensitif();
    }

    public function perluTantanganMfa(): bool
    {
        return $this->isStaf() && ($this->mfa_aktif || $this->wajibMfa());
    }

    // ------------------------------------------------------------- status akun

    public function pesanStatusLogin(): ?string
    {
        return match ($this->status_akun) {
            'menunggu_aktivasi' => 'Akun menunggu aktivasi. Gunakan tautan aktivasi yang dikirim ke email Anda.',
            'ditangguhkan' => 'Akun ditangguhkan, hubungi Admin.',
            'nonaktif' => 'Akun tidak aktif, hubungi Admin.',
            default => null,
        };
    }

    // ------------------------------------------------------------- saldo (per bank sampah)

    /** Saldo pada satu bank sampah, atau total seluruh bank bila $bankId null. */
    public function saldo(?int $bankId = null): float
    {
        if ($bankId !== null) {
            $nilai = $this->mutasiSaldo()->where('bank_sampah_id', $bankId)->orderByDesc('id')->value('saldo_setelah');

            return (float) ($nilai ?? 0);
        }

        return (float) $this->saldoPerBank()->sum('saldo');
    }

    /** @return \Illuminate\Support\Collection<int,array{bank_sampah_id:?int,saldo:float}> */
    public function saldoPerBank()
    {
        return $this->mutasiSaldo()->orderByDesc('id')->get()
            ->unique('bank_sampah_id')
            ->map(fn ($m) => ['bank_sampah_id' => $m->bank_sampah_id, 'saldo' => (float) $m->saldo_setelah])
            ->values();
    }
}
