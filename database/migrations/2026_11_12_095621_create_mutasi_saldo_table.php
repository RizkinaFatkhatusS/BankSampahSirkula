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
        Schema::create('mutasi_saldo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('setoran_id')->nullable()->constrained('setoran')->onDelete('set null');
            $table->enum('tipe', ['setoran', 'pencairan']);
            $table->decimal('nilai', 12, 2);
            $table->decimal('saldo_setelah', 12, 2);
            $table->string('cara_pencairan')->nullable(); // Contoh: Tunai, Transfer E-Wallet
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mutasi_saldo');
    }
};
