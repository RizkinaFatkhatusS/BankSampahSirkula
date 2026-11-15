<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengolahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->cascadeOnDelete();
            $table->enum('kategori_sampah', ['organik', 'anorganik']);
            $table->enum('jenis_olahan', ['pupuk_cair', 'kerajinan', 'energi_lain']);
            $table->decimal('berat_diolah', 10, 2)->nullable();
            $table->date('tanggal');
            $table->text('catatan')->nullable();
            $table->string('foto_dokumentasi')->nullable();
            $table->timestamps();
        });

        // Penelusuran: setoran asal yang diolah
        Schema::create('pengolahan_setoran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengolahan_id')->constrained('pengolahan')->cascadeOnDelete();
            $table->foreignId('setoran_id')->constrained('setoran')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengolahan');
    }
};
