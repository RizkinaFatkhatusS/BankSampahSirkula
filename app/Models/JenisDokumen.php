<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisDokumen extends Model
{
    protected $table = 'jenis_dokumen';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['wajib' => 'boolean', 'aktif' => 'boolean'];
    }

    public function scopeAktif($q)
    {
        return $q->where('aktif', true)->orderBy('urutan')->orderBy('id');
    }
}
