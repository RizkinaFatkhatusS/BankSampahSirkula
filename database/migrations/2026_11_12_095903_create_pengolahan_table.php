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
        Schema::create('pengolahan', function (Blueprint $table) {
           $table->id();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->onDelete('cascade');
            $table->string('kategori_sampah');
            $table->string('jenis_olahan'); // Contoh: Pupuk cair, kerajinan, dll
            $table->date('tanggal');
            $table->text('catatan')->nullable();
            $table->string('foto_dokumentasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengolahan');
    }
};
