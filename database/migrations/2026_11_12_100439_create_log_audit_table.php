<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_audit', function (Blueprint $table) {
            $table->id();
            $table->string('entitas');
            $table->unsignedBigInteger('entitas_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi');
            $table->text('nilai_sebelum')->nullable();
            $table->text('nilai_sesudah')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['entitas', 'entitas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_audit');
    }
};
