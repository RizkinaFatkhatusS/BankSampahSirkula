<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Pengolahan extends Model
{
    protected $table = 'pengolahan';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'berat_diolah' => 'decimal:2'];
    }

    public function produk(): HasMany
    {
        return $this->hasMany(ProdukOlahan::class);
    }

    public function setoranAsal(): BelongsToMany
    {
        return $this->belongsToMany(Setoran::class, 'pengolahan_setoran');
    }

    public function fotoUrl(): ?string
    {
        return $this->foto_dokumentasi ? Storage::url($this->foto_dokumentasi) : null;
    }
}
