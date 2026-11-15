<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('setoran_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('setoran_id')->constrained('setoran')->cascadeOnDelete();
            $table->foreignId('jenis_sampah_id')->constrained('jenis_sampah')->cascadeOnDelete();
            // Terisi saat penimbangan (harga yang berlaku saat itu)
            $table->decimal('harga_per_kg', 12, 2)->nullable();
            $table->decimal('berat_aktual', 10, 2)->nullable();
            $table->decimal('nilai_final', 14, 2)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditimbang_pada')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setoran_item');
    }
};
