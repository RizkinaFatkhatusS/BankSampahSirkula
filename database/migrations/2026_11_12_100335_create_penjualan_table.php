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
        Schema::create('penjualan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('mitra_id')->constrained('mitra')->onDelete('cascade');
        $table->decimal('nilai_transaksi', 12, 2);
        $table->string('bukti_pembayaran')->nullable();
        $table->string('status_pengiriman')->default('diproses');
        $table->date('tanggal');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};
