<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Role tugas dan hak akses Admin (PRD 3.4), dengan nama tabel setara spatie/laravel-permission. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->text('deskripsi')->nullable();
            $table->boolean('aktif')->default(true);
            $table->boolean('sistem')->default(false);   // role sistem Super Admin: tidak dapat diubah/dihapus
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('kelompok');
            $table->string('deskripsi');
            $table->string('jenis', 30);                 // dapat_diberikan|sensitif|khusus_super_admin
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
    }

    public function down(): void
    {
        foreach (['model_has_roles', 'role_has_permissions', 'permissions', 'roles'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
