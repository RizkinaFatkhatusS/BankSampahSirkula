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
        Schema::create('jenis_sampah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->onDelete('cascade');
            $table->string('nama'); // Contoh: Plastik, Kertas, Organik, Logam
            $table->enum('kategori', ['organik', 'anorganik']);
            $table->decimal('harga_per_kg', 10, 2); // Menggunakan decimal sesuai aturan teknis
            $table->date('berlaku_sejak')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jenis_sampah');
    }
};
