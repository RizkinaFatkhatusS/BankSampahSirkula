<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produk_olahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengolahan_id')->constrained('pengolahan')->onDelete('cascade');
            $table->string('nama_produk');
            $table->string('kategori');
            $table->integer('jumlah')->default(0); // Stok
            $table->string('satuan'); // Contoh: Liter, Pcs, Kg
            $table->decimal('harga_jual', 12, 2);
            $table->string('foto')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_olahan');
    }
};
