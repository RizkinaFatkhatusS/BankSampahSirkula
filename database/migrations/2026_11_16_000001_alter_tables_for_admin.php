<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PRD v1.6: role admin/super_admin, status akun, status tayang, status setoran Ditunda,
 * saldo per bank sampah, kolom log audit tambahan, dan kategori yang mengikuti data master.
 * Migration ini aditif: data yang sudah ada tetap aman.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('customer')->change();   // customer|supplier|admin|super_admin
            $table->string('password')->nullable()->change();             // kosong sampai aktivasi
            $table->string('status_akun', 30)->default('aktif')->index();
            $table->text('alasan_status')->nullable();
            $table->timestamp('terakhir_login')->nullable();
            $table->timestamp('batas_penyelesaian')->nullable();
            $table->boolean('mfa_aktif')->default(false);
            $table->text('mfa_secret')->nullable();
        });

        Schema::table('bank_sampah', function (Blueprint $table) {
            $table->string('status_tayang', 20)->default('tayang')->index();
        });

        Schema::table('setoran', function (Blueprint $table) {
            $table->string('status', 30)->default('diajukan')->change();
            $table->string('status_sebelum_ditunda', 30)->nullable();
            $table->string('dibatalkan_oleh', 20)->nullable();
            $table->timestamp('ditunda_pada')->nullable();
        });

        // Kategori mengikuti data master (FR-42), jadi tidak lagi dibatasi enum
        Schema::table('jenis_sampah', fn (Blueprint $t) => $t->string('kategori', 50)->change());
        Schema::table('pengolahan', function (Blueprint $t) {
            $t->string('kategori_sampah', 50)->change();
            $t->string('jenis_olahan', 50)->change();
        });
        Schema::table('produk_olahan', fn (Blueprint $t) => $t->string('kategori', 50)->change());

        Schema::table('mutasi_saldo', function (Blueprint $table) {
            $table->unsignedBigInteger('bank_sampah_id')->nullable()->index(); // saldo dicatat per bank sampah
        });

        Schema::table('log_audit', function (Blueprint $table) {
            $table->text('alasan')->nullable();
            $table->string('ip_address', 45)->nullable();
        });

        $this->isiSaldoPerBank();
    }

    /** Mengisi bank_sampah_id pada mutasi lama dan menghitung ulang saldo_setelah per (customer, bank). */
    private function isiSaldoPerBank(): void
    {
        DB::table('mutasi_saldo')->whereNotNull('setoran_id')->orderBy('id')->each(function ($m) {
            $bank = DB::table('setoran')->where('id', $m->setoran_id)->value('bank_sampah_id');
            if ($bank) {
                DB::table('mutasi_saldo')->where('id', $m->id)->update(['bank_sampah_id' => $bank]);
            }
        });

        DB::table('kas_bank_sampah')->where('sumber', 'pencairan')->orderBy('id')->each(function ($k) {
            if (preg_match('/^mutasi_saldo#(\d+)$/', (string) $k->rujukan, $mm)) {
                DB::table('mutasi_saldo')->where('id', $mm[1])->update(['bank_sampah_id' => $k->bank_sampah_id]);
            }
        });

        $saldo = [];
        DB::table('mutasi_saldo')->orderBy('id')->each(function ($m) use (&$saldo) {
            $kunci = $m->customer_id.'|'.($m->bank_sampah_id ?? 0);
            $saldo[$kunci] = ($saldo[$kunci] ?? 0) + ($m->tipe === 'setoran' ? 1 : -1) * (float) $m->nilai;
            DB::table('mutasi_saldo')->where('id', $m->id)->update(['saldo_setelah' => round($saldo[$kunci], 2)]);
        });
    }

    public function down(): void
    {
        Schema::table('log_audit', fn (Blueprint $t) => $t->dropColumn(['alasan', 'ip_address']));
        Schema::table('mutasi_saldo', fn (Blueprint $t) => $t->dropColumn('bank_sampah_id'));
        Schema::table('setoran', fn (Blueprint $t) => $t->dropColumn(['status_sebelum_ditunda', 'dibatalkan_oleh', 'ditunda_pada']));
        Schema::table('bank_sampah', fn (Blueprint $t) => $t->dropColumn('status_tayang'));
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['status_akun', 'alasan_status', 'terakhir_login', 'batas_penyelesaian', 'mfa_aktif', 'mfa_secret']));
    }
};
