<?php

namespace App\Services;

use App\Models\BankSampah;
use App\Models\JenisSampah;
use App\Models\KasBankSampah;
use App\Models\MutasiSaldo;
use App\Models\Setoran;
use App\Models\User;
use App\Notifications\SetoranNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Seluruh logika setoran, nilai final, dan saldo ada di sini dan dijalankan
 * di dalam DB transaction (PRD 5.3) agar data tetap konsisten.
 */
class SetoranService
{
    public static function rp(float|string $n): string
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }

    /** Estimasi = jumlah harga per kg tiap jenis terpilih (PRD FR-08 / OI-22). Customer tidak mengisi berat. */
    public function hitungEstimasi(BankSampah $bank, array $jenisIds): array
    {
        $jenis = $bank->jenisSampah()->where('aktif', true)->whereIn('id', $jenisIds)->get();

        return [$jenis, round((float) $jenis->sum('harga_per_kg'), 2)];
    }

    public function ajukan(User $customer, BankSampah $bank, array $data, UploadedFile $foto): Setoran
    {
        // Bank sampah yang ditangguhkan/dihentikan/tidak tayang tidak menerima setoran baru (FR-39, FR-50)
        if (! BankSampah::tampil()->whereKey($bank->id)->exists()) {
            throw ValidationException::withMessages(['bank_sampah_id' => 'Bank sampah ini sedang tidak menerima setoran. Silakan pilih bank sampah lain.']);
        }

        [$jenis, $estimasi] = $this->hitungEstimasi($bank, $data['jenis_sampah_ids']);

        if ($jenis->count() !== count(array_unique($data['jenis_sampah_ids']))) {
            throw ValidationException::withMessages(['jenis_sampah_ids' => 'Ada jenis sampah yang tidak tersedia di bank sampah ini.']);
        }

        return DB::transaction(function () use ($customer, $bank, $data, $foto, $jenis, $estimasi) {
            $setoran = Setoran::create([
                'customer_id' => $customer->id,
                'bank_sampah_id' => $bank->id,
                'foto_bukti' => $foto->store('setoran', 'public'),
                'estimasi_nilai' => $estimasi,
                'metode_penyerahan' => $data['metode_penyerahan'],
                'alamat_jemput' => $data['metode_penyerahan'] === 'dijemput_kurir' ? $data['alamat_jemput'] : null,
                'jadwal_jemput' => $data['metode_penyerahan'] === 'dijemput_kurir' ? $data['jadwal_jemput'] : null,
                'status' => 'diajukan',
            ]);

            foreach ($jenis as $j) {
                $setoran->items()->create(['jenis_sampah_id' => $j->id]);
            }

            $this->notifSupplier($bank, 'Setoran baru masuk', "{$customer->nama} mengajukan setoran ({$jenis->pluck('nama')->join(', ')}).", $setoran);
            $this->notif($customer, 'Setoran diajukan', 'Setoran Anda sudah diteruskan ke bank sampah. Nilai pasti ditetapkan setelah penimbangan.', $setoran);

            return $setoran;
        });
    }

    public function setujui(Setoran $setoran, User $supplier): void
    {
        $this->pastikanStatus($setoran, ['diajukan'], 'Hanya setoran berstatus Diajukan yang dapat disetujui.');

        DB::transaction(function () use ($setoran, $supplier) {
            if ($setoran->metode_penyerahan === 'dijemput_kurir') {
                $this->ubahStatus($setoran, 'menunggu_penjemputan', $supplier);
                $this->catatPenjemputan($setoran, 'menunggu_penjemputan', $supplier);
                $pesan = "Setoran disetujui. Kurir akan menjemput pada {$setoran->jadwal_jemput}.";
            } else {
                $this->ubahStatus($setoran, 'disetujui', $supplier);
                $pesan = 'Setoran disetujui. Silakan antar sampah ke bank sampah sesuai jam operasional.';
            }
            $this->notif($setoran->customer, 'Setoran disetujui', $pesan, $setoran);
        });
    }

    public function tolak(Setoran $setoran, User $supplier, string $alasan): void
    {
        $this->pastikanStatus($setoran, Setoran::STATUS_SEBELUM_DITERIMA, 'Setoran yang sudah diterima tidak dapat ditolak.');

        DB::transaction(function () use ($setoran, $supplier, $alasan) {
            $setoran->update(['alasan_penolakan' => $alasan]);
            $this->ubahStatus($setoran, 'ditolak', $supplier);
            $this->notif($setoran->customer, 'Setoran ditolak', "Setoran ditolak bank sampah. Alasan: {$alasan}", $setoran, 'peringatan', true);
        });
    }

    public function batalkan(Setoran $setoran, User $customer): void
    {
        $this->pastikanStatus($setoran, Setoran::STATUS_BISA_DIBATALKAN, 'Setoran sudah diterima bank sampah dan tidak dapat dibatalkan.');

        DB::transaction(function () use ($setoran, $customer) {
            $setoran->update(['dibatalkan_oleh' => 'customer']);
            $this->ubahStatus($setoran, 'dibatalkan', $customer);
            $this->notif($customer, 'Setoran dibatalkan', 'Setoran Anda telah dibatalkan.', $setoran, 'info', true);
            $this->notifSupplier($setoran->bankSampah, 'Setoran dibatalkan', "{$customer->nama} membatalkan setorannya #{$setoran->id}.", $setoran);
        });
    }

    /** Status penjemputan: menunggu_penjemputan, dalam_penjemputan, selesai (sampah tiba = diterima). */
    public function ubahPenjemputan(Setoran $setoran, User $supplier, string $status): void
    {
        if ($setoran->metode_penyerahan !== 'dijemput_kurir') {
            throw ValidationException::withMessages(['status' => 'Setoran ini diantar sendiri, bukan penjemputan.']);
        }
        $this->pastikanStatus($setoran, ['menunggu_penjemputan', 'dalam_penjemputan'], 'Status penjemputan tidak dapat diubah pada tahap ini.');

        DB::transaction(function () use ($setoran, $supplier, $status) {
            $this->catatPenjemputan($setoran, $status, $supplier);

            if ($status === 'selesai') {
                $this->ubahStatus($setoran, 'diterima', $supplier);
                $this->notif($setoran->customer, 'Sampah diterima', 'Sampah Anda sudah diterima bank sampah dan akan ditimbang.', $setoran);

                return;
            }
            $this->ubahStatus($setoran, $status, $supplier);
            $label = $status === 'dalam_penjemputan' ? 'Kurir sedang menuju lokasi Anda.' : 'Penjemputan menunggu jadwal.';
            $this->notif($setoran->customer, 'Status penjemputan berubah', $label, $setoran);
        });
    }

    public function terima(Setoran $setoran, User $supplier): void
    {
        $this->pastikanStatus($setoran, Setoran::STATUS_SEBELUM_DITERIMA, 'Setoran ini tidak dapat ditandai diterima.');

        DB::transaction(function () use ($setoran, $supplier) {
            if ($setoran->metode_penyerahan === 'dijemput_kurir') {
                $this->catatPenjemputan($setoran, 'selesai', $supplier);
            }
            $this->ubahStatus($setoran, 'diterima', $supplier);
            $this->notif($setoran->customer, 'Sampah diterima', 'Sampah Anda sudah diterima bank sampah dan akan ditimbang.', $setoran);
        });
    }

    /**
     * Catat berat aktual per jenis, hitung nilai final dengan HARGA SAAT PENIMBANGAN,
     * tambah saldo Customer, tulis mutasi saldo, audit, dan kirim notifikasi (FR-18..20, FR-15).
     *
     * @param  array<int,numeric>  $beratPerItem  [setoran_item_id => berat_kg]
     */
    public function timbang(Setoran $setoran, User $supplier, array $beratPerItem): Setoran
    {
        return DB::transaction(function () use ($setoran, $supplier, $beratPerItem) {
            $setoran = Setoran::whereKey($setoran->id)->lockForUpdate()->firstOrFail();
            $this->pastikanStatus($setoran, ['diterima'], 'Setoran harus berstatus Diterima sebelum ditimbang.');

            $items = $setoran->items()->with('jenisSampah')->get();
            $totalBerat = 0.0;
            $totalNilai = 0.0;
            $sebelum = [];
            $sesudah = [];

            foreach ($items as $item) {
                $berat = round((float) ($beratPerItem[$item->id] ?? 0), 2);
                $harga = (float) $item->jenisSampah->harga_per_kg;   // harga yang berlaku sekarang
                $nilai = round($berat * $harga, 2);

                $sebelum[$item->id] = ['berat' => $item->berat_aktual, 'nilai' => $item->nilai_final];
                $item->update([
                    'harga_per_kg' => $harga,
                    'berat_aktual' => $berat,
                    'nilai_final' => $nilai,
                    'supplier_id' => $supplier->id,
                    'ditimbang_pada' => now(),
                ]);
                $sesudah[$item->id] = ['berat' => $berat, 'harga' => $harga, 'nilai' => $nilai];

                $totalBerat += $berat;
                $totalNilai += $nilai;
            }

            if ($totalBerat <= 0) {
                throw ValidationException::withMessages(['berat' => 'Isi berat aktual minimal pada satu jenis sampah.']);
            }

            $customer = User::whereKey($setoran->customer_id)->lockForUpdate()->firstOrFail();
            $saldoLama = $customer->saldo($setoran->bank_sampah_id); // saldo dicatat per bank sampah
            $saldoBaru = round($saldoLama + $totalNilai, 2);

            $mutasi = MutasiSaldo::create([
                'customer_id' => $customer->id,
                'bank_sampah_id' => $setoran->bank_sampah_id,
                'setoran_id' => $setoran->id,
                'tipe' => 'setoran',
                'nilai' => $totalNilai,
                'saldo_setelah' => $saldoBaru,
                'tanggal' => now()->toDateString(),
            ]);

            $setoran->update([
                'total_berat' => $totalBerat,
                'nilai_final' => $totalNilai,
                'ditimbang_pada' => now(),
            ]);
            $this->ubahStatus($setoran, 'ditimbang', $supplier);
            $this->ubahStatus($setoran, 'selesai', $supplier); // saldo sudah diperbarui -> setoran tuntas

            AuditLogger::catat('setoran', $setoran->id, 'timbang', $sebelum, $sesudah);
            AuditLogger::catat('mutasi_saldo', $mutasi->id, 'saldo_bertambah', ['saldo' => $saldoLama], ['saldo' => $saldoBaru]);

            $this->notif(
                $customer,
                'Setoran selesai ditimbang',
                'Berat aktual '.rtrim(rtrim(number_format($totalBerat, 2, ',', '.'), '0'), ',').' kg, nilai final '.self::rp($totalNilai).'. Saldo terbaru Anda '.self::rp($saldoBaru).'.',
                $setoran,
                'sukses',
                true,
            );

            return $setoran->fresh('items.jenisSampah');
        });
    }

    /** Pencairan dilakukan di luar sistem; Supplier hanya mencatatnya (FR-32). */
    public function cairkan(User $supplier, User $customer, float $nilai, string $cara): MutasiSaldo
    {
        return DB::transaction(function () use ($supplier, $customer, $nilai, $cara) {
            $customer = User::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $bankId = $supplier->bank_sampah_id;
            $saldoLama = $customer->saldo($bankId); // hanya saldo di bank sampah ini yang dapat dicairkan

            if ($nilai <= 0 || $nilai > $saldoLama) {
                throw ValidationException::withMessages(['nilai' => 'Nilai pencairan harus lebih dari 0 dan tidak melebihi saldo ('.self::rp($saldoLama).').']);
            }

            $saldoBaru = round($saldoLama - $nilai, 2);
            $mutasi = MutasiSaldo::create([
                'customer_id' => $customer->id,
                'bank_sampah_id' => $bankId,
                'tipe' => 'pencairan',
                'nilai' => $nilai,
                'saldo_setelah' => $saldoBaru,
                'cara_pencairan' => $cara,
                'tanggal' => now()->toDateString(),
            ]);

            KasBankSampah::create([
                'bank_sampah_id' => $bankId,
                'jenis' => 'keluar',
                'sumber' => 'pencairan',
                'nominal' => $nilai,
                'rujukan' => "mutasi_saldo#{$mutasi->id}",
                'tanggal' => now()->toDateString(),
            ]);

            AuditLogger::catat('mutasi_saldo', $mutasi->id, 'pencairan', ['saldo' => $saldoLama], ['saldo' => $saldoBaru]);
            $this->notif($customer, 'Pencairan saldo dicatat', self::rp($nilai)." dicairkan via {$cara}. Saldo terbaru ".self::rp($saldoBaru).'.', null, 'info', true);

            return $mutasi;
        });
    }

    // ---------------------------------------------------------------- helper

    private function pastikanStatus(Setoran $setoran, array $diizinkan, string $pesan): void
    {
        if (! in_array($setoran->status, $diizinkan, true)) {
            throw ValidationException::withMessages(['status' => $pesan]);
        }
    }

    private function ubahStatus(Setoran $setoran, string $status, User $pelaku): void
    {
        $lama = $setoran->status;
        $setoran->update(['status' => $status]);
        AuditLogger::catat('setoran', $setoran->id, 'status', ['status' => $lama], ['status' => $status, 'oleh' => $pelaku->id]);
    }

    private function catatPenjemputan(Setoran $setoran, string $status, User $pencatat): void
    {
        $setoran->riwayatPenjemputan()->create(['status' => $status, 'pencatat_id' => $pencatat->id, 'waktu_perubahan' => now()]);
    }

    /** Notifikasi dalam aplikasi; $email = true bila peristiwa juga dikirim lewat email (Matriks notifikasi). */
    private function notif(User $user, string $judul, string $pesan, ?Setoran $setoran = null, string $tipe = 'info', bool $email = false): void
    {
        $user->notify(new SetoranNotification($judul, $pesan, $setoran?->id, $tipe));

        if ($email) {
            EmailService::kirimKeUser(
                $user,
                'notifikasi_customer',
                $judul,
                [$pesan],
                $setoran ? 'Lihat setoran' : 'Buka SIRKULA',
                $setoran ? route('customer.setoran.show', $setoran->id) : route('customer.riwayat'),
            );
        }
    }

    private function notifSupplier(BankSampah $bank, string $judul, string $pesan, Setoran $setoran): void
    {
        User::where('role', 'supplier')->where('bank_sampah_id', $bank->id)->get()
            ->each(fn (User $s) => $this->notif($s, $judul, $pesan, $setoran));
    }
}
