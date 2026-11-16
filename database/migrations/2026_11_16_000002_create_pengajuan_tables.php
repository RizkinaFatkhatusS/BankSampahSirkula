<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Data master persyaratan dokumen pendaftaran Supplier (FR-42)
        Schema::create('jenis_dokumen', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('wajib')->default(true);
            $table->boolean('aktif')->default(true);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->unsignedTinyInteger('min_berkas')->default(1);
            $table->timestamps();
        });

        Schema::create('pengajuan_supplier', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pengajuan', 20)->unique();
            $table->string('nama_bank_sampah');
            $table->string('alamat');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('nomor_kontak', 30);
            $table->string('jam_operasional');
            $table->string('nama_penanggung_jawab');
            $table->string('email')->index();
            $table->string('nomor_hp', 20);
            $table->string('status', 30)->default('menunggu_verifikasi')->index();
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token_status', 64)->unique();          // tautan pribadi Cek Status Pengajuan
            $table->text('catatan_admin')->nullable();             // catatan untuk calon Supplier
            $table->text('catatan_verifikasi')->nullable();        // catatan internal (hasil telepon/kunjungan)
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->foreignId('dibuat_oleh_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('bank_sampah_id')->nullable()->constrained('bank_sampah')->nullOnDelete();
            $table->timestamp('pengingat_terakhir_at')->nullable();
            $table->timestamps();
        });

        Schema::create('dokumen_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan_supplier')->cascadeOnDelete();
            $table->foreignId('jenis_dokumen_id')->constrained('jenis_dokumen');
            $table->string('path_berkas');                          // disk private
            $table->string('nama_asli');
            $table->unsignedInteger('ukuran');
            $table->string('status_pemeriksaan', 20)->default('belum'); // belum|sesuai|perlu_diperbaiki
            $table->text('catatan')->nullable();
            $table->timestamp('diunggah_pada')->useCurrent();
        });

        Schema::create('riwayat_status_pengajuan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuan_supplier')->cascadeOnDelete();
            $table->string('status', 30);
            $table->text('catatan')->nullable();
            $table->foreignId('oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waktu')->useCurrent();
        });

        // Tautan aktivasi / undangan / reset kata sandi: sekali pakai, berlaku terbatas
        Schema::create('token_akun', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('jenis', 30);                            // aktivasi_supplier|undangan_admin|reset_password
            $table->string('token_hash', 64)->unique();
            $table->timestamp('kadaluarsa_pada');
            $table->timestamp('dipakai_pada')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['token_akun', 'riwayat_status_pengajuan', 'dokumen_pengajuan', 'pengajuan_supplier', 'jenis_dokumen'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
