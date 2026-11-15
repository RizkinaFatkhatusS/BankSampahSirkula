<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mitra extends Model
{
    protected $table = 'mitra';

    protected $guarded = [];

    public function penjualan(): HasMany
    {
        return $this->hasMany(Penjualan::class);
    }
}
