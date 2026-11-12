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
        Schema::create('setoran', function (Blueprint $table) {
           $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('bank_sampah_id')->constrained('bank_sampah')->onDelete('cascade');
            $table->string('foto_bukti');
            $table->decimal('estimasi_nilai', 12, 2)->default(0);
            $table->enum('metode_penyerahan', ['dijemput_kurir', 'antar_sendiri']);
            $table->text('alamat_jemput')->nullable();
            $table->string('jadwal_jemput')->nullable();
            $table->enum('status', [
                'diajukan', 'disetujui', 'menunggu_penjemputan', 
                'dalam_penjemputan', 'diterima', 'ditimbang', 
                'selesai', 'dibatalkan', 'ditolak'
            ])->default('diajukan');
            $table->text('alasan_penolakan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('setoran');
    }
};
