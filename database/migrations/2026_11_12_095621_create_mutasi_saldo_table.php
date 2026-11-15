<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_saldo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('setoran_id')->nullable()->constrained('setoran')->nullOnDelete();
            $table->enum('tipe', ['setoran', 'pencairan']);
            $table->decimal('nilai', 14, 2);
            $table->decimal('saldo_setelah', 14, 2);
            $table->string('cara_pencairan')->nullable();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_saldo');
    }
};
