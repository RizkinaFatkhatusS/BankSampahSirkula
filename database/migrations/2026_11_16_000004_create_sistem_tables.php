<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_email', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 50);
            $table->string('penerima');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('pengajuan_id')->nullable();
            $table->string('status', 15)->default('antre')->index(); // antre|terkirim|gagal
            $table->unsignedTinyInteger('percobaan')->default(0);
            $table->text('pesan_error')->nullable();
            $table->longText('muatan')->nullable();                  // isi email (JSON) agar dapat dikirim ulang
            $table->timestamp('dikirim_pada')->nullable();
            $table->timestamps();
        });

        Schema::create('log_akses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis', 30)->index();   // login|login_gagal|logout|penolakan_403|buka_dokumen|perubahan_role|mfa
            $table->string('rute')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('detail')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('pengaturan_sistem', function (Blueprint $table) {
            $table->string('kunci')->primary();
            $table->text('nilai');
            $table->unsignedBigInteger('diubah_oleh')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('master_kategori', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 30);              // kategori_sampah|kategori_olahan|satuan
            $table->string('kode', 50);
            $table->string('nama');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->unique(['tipe', 'kode']);
        });
    }

    public function down(): void
    {
        foreach (['master_kategori', 'pengaturan_sistem', 'log_akses', 'log_email'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
