<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produk_olahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->cascadeOnDelete();
            $table->foreignId('pengolahan_id')->nullable()->constrained('pengolahan')->nullOnDelete();
            $table->string('nama_produk');
            $table->enum('kategori', ['pupuk_cair', 'kerajinan', 'energi_lain']);
            $table->decimal('jumlah', 12, 2)->default(0);
            $table->string('satuan', 20);
            $table->decimal('harga_jual', 14, 2);
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produk_olahan');
    }
};
