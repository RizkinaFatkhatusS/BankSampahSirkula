<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogEmail extends Model
{
    protected $table = 'log_email';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['muatan' => 'array', 'dikirim_pada' => 'datetime'];
    }
}
