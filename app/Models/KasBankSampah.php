<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KasBankSampah extends Model
{
    protected $table = 'kas_bank_sampah';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['nominal' => 'decimal:2', 'tanggal' => 'date'];
    }
}
