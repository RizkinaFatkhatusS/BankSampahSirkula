<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankSampah extends Model
{
    use HasFactory;

    protected $table = 'bank_sampah'; // Sesuai dengan nama tabel di migration

    protected $guarded = []; // Mengizinkan semua kolom diisi
}
