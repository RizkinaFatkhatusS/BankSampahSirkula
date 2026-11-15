<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusPenjemputan extends Model
{
    protected $table = 'status_penjemputan';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['waktu_perubahan' => 'datetime'];
    }
}
