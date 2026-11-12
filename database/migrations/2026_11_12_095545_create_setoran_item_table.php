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
        Schema::create('setoran_item', function (Blueprint $table) {
           $table->id();
            $table->foreignId('setoran_id')->constrained('setoran')->onDelete('cascade');
            $table->foreignId('jenis_sampah_id')->constrained('jenis_sampah')->onDelete('cascade');
            $table->decimal('harga_per_kg', 10, 2); // Harga saat penimbangan
            $table->decimal('berat_aktual', 8, 2); // Menggunakan decimal, bukan float
            $table->decimal('nilai_final', 12, 2); // Dihitung dari berat aktual x harga
            $table->unsignedBigInteger('supplier_id'); // Petugas yang mencatat
            $table->timestamp('ditimbang_pada')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setoran_item');
    }
};
