<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_bank_sampah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->cascadeOnDelete();
            $table->enum('jenis', ['masuk', 'keluar']);
            $table->enum('sumber', ['penjualan', 'pencairan', 'lain-lain']);
            $table->decimal('nominal', 14, 2);
            $table->string('rujukan')->nullable();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_bank_sampah');
    }
};
