<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenAkun extends Model
{
    protected $table = 'token_akun';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['kadaluarsa_pada' => 'datetime', 'dipakai_pada' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function berlaku(): bool
    {
        return $this->dipakai_pada === null && $this->kadaluarsa_pada->isFuture();
    }
}
