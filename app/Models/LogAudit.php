<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAudit extends Model
{
    protected $table = 'log_audit';

    public $timestamps = false;

    protected $guarded = [];
}
