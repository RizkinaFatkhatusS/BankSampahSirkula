<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->cascadeOnDelete();
            $table->foreignId('mitra_id')->constrained('mitra')->cascadeOnDelete();
            $table->enum('hasil_komunikasi', ['disetujui', 'ditolak'])->default('disetujui');
            $table->decimal('nilai_transaksi', 14, 2);
            $table->string('bukti_pembayaran')->nullable();
            $table->enum('status_pengiriman', ['diproses', 'dikirim', 'diterima'])->default('diproses');
            $table->text('catatan')->nullable();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};
