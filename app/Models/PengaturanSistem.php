<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanSistem extends Model
{
    protected $table = 'pengaturan_sistem';

    protected $primaryKey = 'kunci';

    protected $keyType = 'string';

    public $incrementing = false;

    public const CREATED_AT = null;

    protected $guarded = [];
}
