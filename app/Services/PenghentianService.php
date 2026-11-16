<?php

namespace App\Services;

use App\Models\BankSampah;
use App\Models\MutasiSaldo;
use App\Models\Setoran;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Perubahan status akun Supplier dan dampaknya pada setoran serta saldo Customer (FR-39, FR-50).
 * Semua dijalankan dalam satu DB transaction. Data dan saldo tidak pernah dihapus.
 */
class PenghentianService
{
    public const LABEL = [
        'menunggu_aktivasi' => 'Menunggu aktivasi',
        'aktif' => 'Aktif',
        'ditangguhkan' => 'Ditangguhkan',
        'dalam_penyelesaian' => 'Dalam penyelesaian',
        'nonaktif' => 'Nonaktif',
    ];

    /** status asal => status tujuan yang diizinkan (PRD bagian 4, Status akun Supplier) */
    private const TRANSISI = [
        'aktif' => ['ditangguhkan', 'dalam_penyelesaian', 'nonaktif'],
        'ditangguhkan' => ['aktif', 'dalam_penyelesaian', 'nonaktif'],
        'dalam_penyelesaian' => ['nonaktif', 'aktif'],
        'nonaktif' => ['aktif'],
    ];

    /** Status tujuan yang diizinkan dari status tertentu. */
    public static function tujuan(string $status): array
    {
        return self::TRANSISI[$status] ?? [];
    }

    private const PRA_DITERIMA = ['diajukan', 'disetujui', 'menunggu_penjemputan', 'dalam_penjemputan'];

    /** Ringkasan dampak yang ditampilkan sebelum status diubah. */
    public function dampak(int $bankId): array
    {
        $perStatus = Setoran::where('bank_sampah_id', $bankId)->whereIn('status', Setoran::STATUS_BELUM_FINAL)
            ->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status')->map(fn ($j) => (int) $j)->all();

        return [
            'setoran_per_status' => $perStatus,
            'setoran_belum_final' => array_sum($perStatus),
            'saldo_belum_dicairkan' => $this->saldoBelumDicairkan($bankId),
            'customer_bersaldo' => $this->customerBersaldo($bankId)->count(),
        ];
    }

    /** Customer yang masih punya saldo positif pada bank sampah ini: [customer_id => saldo]. */
    public function customerBersaldo(int $bankId): Collection
    {
        return MutasiSaldo::where('bank_sampah_id', $bankId)->orderByDesc('id')->get()
            ->unique('customer_id')->filter(fn ($m) => (float) $m->saldo_setelah > 0)
            ->mapWithKeys(fn ($m) => [$m->customer_id => (float) $m->saldo_setelah]);
    }

    public function saldoBelumDicairkan(int $bankId): float
    {
        return round((float) $this->customerBersaldo($bankId)->sum(), 2);
    }

    /**
     * @param  ?User  $admin  null bila dijalankan sistem (peralihan otomatis)
     */
    public function ubah(?User $admin, User $supplier, string $baru, string $alasan, ?string $rencana = null): array
    {
        if (! $supplier->isSupplier() || ! $supplier->bank_sampah_id) {
            throw ValidationException::withMessages(['status' => 'Akun ini bukan akun Supplier.']);
        }
        $lama = $supplier->status_akun;
        if (! in_array($baru, self::TRANSISI[$lama] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Perubahan status dari '.(self::LABEL[$lama] ?? $lama).' ke '.(self::LABEL[$baru] ?? $baru).' tidak diizinkan.']);
        }
        if (blank($alasan)) {
            throw ValidationException::withMessages(['alasan' => 'Alasan wajib diisi.']);
        }

        $bank = BankSampah::findOrFail($supplier->bank_sampah_id);
        $dampak = $this->dampak($bank->id);

        if ($baru === 'nonaktif' && ($dampak['saldo_belum_dicairkan'] > 0 || $dampak['setoran_belum_final'] > 0) && $admin) {
            // Nonaktif saat masih ada saldo atau setoran belum final hanya untuk hak akses khusus (FR-50)
            if (! $admin->punya('supplier.nonaktifkan')) {
                throw ValidationException::withMessages(['status' => 'Masih ada saldo belum dicairkan atau setoran belum final. Menonaktifkan hanya dapat dilakukan Admin dengan hak akses supplier.nonaktifkan.']);
            }
            if (blank($rencana)) {
                throw ValidationException::withMessages(['rencana' => 'Rencana penyelesaian wajib diisi karena masih ada saldo atau setoran belum final.']);
            }
        }

        return DB::transaction(function () use ($admin, $supplier, $lama, $baru, $alasan, $rencana, $bank, $dampak) {
            $batas = $baru === 'dalam_penyelesaian' ? now()->addDays(Pengaturan::get('penyelesaian_hari')) : null;
            $supplier->update(['status_akun' => $baru, 'alasan_status' => $alasan, 'batas_penyelesaian' => $batas]);

            $terdampak = collect();   // customer_id => ['dibatalkan'=>n, 'ditunda'=>n, 'dipulihkan'=>n]

            if ($baru === 'ditangguhkan') {
                $terdampak = $this->tundakan($bank->id);
            } elseif (in_array($baru, ['dalam_penyelesaian', 'nonaktif'], true)) {
                $terdampak = $this->batalkanOtomatis($bank->id, $baru === 'nonaktif' ? 'Bank sampah dihentikan.' : 'Bank sampah berhenti menerima setoran.');
            } elseif ($baru === 'aktif') {
                $terdampak = $this->pulihkan($bank->id);
            }

            AuditLogger::catat('users', $supplier->id, 'status_akun', ['status_akun' => $lama], [
                'status_akun' => $baru, 'rencana_penyelesaian' => $rencana, 'dampak' => $dampak,
            ], $alasan);

            $this->beritahuSupplier($supplier, $bank, $baru, $alasan, $batas);
            $this->beritahuCustomer($bank, $baru, $terdampak, $batas);

            return ['dampak' => $dampak, 'customer_terdampak' => $terdampak->count()];
        });
    }

    private function tundakan(int $bankId): Collection
    {
        $hasil = collect();
        Setoran::where('bank_sampah_id', $bankId)->whereIn('status', self::PRA_DITERIMA)->get()->each(function (Setoran $s) use (&$hasil) {
            $s->update(['status_sebelum_ditunda' => $s->status, 'status' => 'ditunda', 'ditunda_pada' => now()]);
            AuditLogger::catat('setoran', $s->id, 'status', ['status' => $s->status_sebelum_ditunda], ['status' => 'ditunda']);
            $hasil[$s->customer_id] = ['ditunda' => ($hasil[$s->customer_id]['ditunda'] ?? 0) + 1];
        });

        return $hasil;
    }

    private function batalkanOtomatis(int $bankId, string $alasan): Collection
    {
        $hasil = collect();
        Setoran::where('bank_sampah_id', $bankId)->whereIn('status', [...self::PRA_DITERIMA, 'ditunda'])->get()->each(function (Setoran $s) use (&$hasil, $alasan) {
            $lama = $s->status;
            $s->update(['status' => 'dibatalkan', 'dibatalkan_oleh' => 'sistem', 'alasan_penolakan' => $alasan]);
            AuditLogger::catat('setoran', $s->id, 'status', ['status' => $lama], ['status' => 'dibatalkan', 'oleh' => 'sistem'], $alasan);
            $hasil[$s->customer_id] = ['dibatalkan' => ($hasil[$s->customer_id]['dibatalkan'] ?? 0) + 1];
        });

        return $hasil;
    }

    private function pulihkan(int $bankId): Collection
    {
        $hasil = collect();
        Setoran::where('bank_sampah_id', $bankId)->where('status', 'ditunda')->get()->each(function (Setoran $s) use (&$hasil) {
            $kembali = $s->status_sebelum_ditunda ?: 'diajukan';
            $s->update(['status' => $kembali, 'status_sebelum_ditunda' => null, 'ditunda_pada' => null]);
            AuditLogger::catat('setoran', $s->id, 'status', ['status' => 'ditunda'], ['status' => $kembali]);
            $hasil[$s->customer_id] = ['dipulihkan' => ($hasil[$s->customer_id]['dipulihkan'] ?? 0) + 1];
        });

        return $hasil;
    }

    private function beritahuSupplier(User $supplier, BankSampah $bank, string $status, string $alasan, $batas): void
    {
        $label = self::LABEL[$status];
        $pesan = "Status akun Supplier {$bank->nama} berubah menjadi {$label}. Alasan: {$alasan}";
        if ($status === 'dalam_penyelesaian') {
            $pesan .= ' Anda masih dapat login terbatas untuk menyelesaikan setoran yang sudah diterima, mencatat pencairan saldo, dan mengunduh laporan hingga '.$batas->translatedFormat('d F Y').'.';
        }
        if ($status === 'aktif') {
            $pesan = "Akun Supplier {$bank->nama} diaktifkan kembali.";
        }

        if (in_array($status, ['aktif', 'dalam_penyelesaian'], true)) {
            Notifier::user($supplier, 'Status akun berubah', $pesan, route('supplier.beranda'), $status === 'aktif' ? 'sukses' : 'peringatan');
        }
        EmailService::kirimKeUser($supplier, 'status_akun_supplier', 'Status akun Supplier berubah: '.$label, [$pesan], in_array($status, ['aktif', 'dalam_penyelesaian'], true) ? 'Masuk ke SIRKULA' : null, in_array($status, ['aktif', 'dalam_penyelesaian'], true) ? route('login') : null);
    }

    private function beritahuCustomer(BankSampah $bank, string $status, Collection $terdampak, $batas): void
    {
        $saldo = $this->customerBersaldo($bank->id);
        $kontak = "Kontak bank sampah: {$bank->nomor_kontak}.";

        if ($status === 'aktif') {
            // Pemulihan: cukup memberi tahu Customer yang setorannya dilanjutkan
            User::whereIn('id', $terdampak->keys())->get()->each(fn (User $c) => Notifier::user($c, 'Setoran dilanjutkan', "Bank sampah {$bank->nama} beroperasi kembali. Setoran Anda yang tertunda dilanjutkan.", route('customer.riwayat'), 'sukses'));

            return;
        }

        $ids = $terdampak->keys()->merge($saldo->keys())->unique();
        User::whereIn('id', $ids)->get()->each(function (User $c) use ($bank, $status, $terdampak, $saldo, $batas, $kontak) {
            $info = $terdampak[$c->id] ?? [];
            $s = (float) ($saldo[$c->id] ?? 0);
            $baris = [];

            if ($status === 'ditangguhkan') {
                $judul = "Bank sampah {$bank->nama} ditangguhkan sementara";
                $baris[] = 'Bank sampah ini dihentikan sementara dan tidak menerima setoran baru.';
                if (! empty($info['ditunda'])) {
                    $baris[] = "{$info['ditunda']} setoran Anda yang belum diterima berstatus Ditunda. Anda dapat membatalkannya, atau setoran dilanjutkan bila bank sampah dipulihkan. Setoran yang tertunda lebih dari ".Pengaturan::get('ditunda_batas_hari').' hari dibatalkan otomatis.';
                }
            } elseif ($status === 'dalam_penyelesaian') {
                $judul = "Bank sampah {$bank->nama} berhenti menerima setoran";
                $baris[] = 'Bank sampah ini sedang dalam penyelesaian dan tidak menerima setoran baru.';
                if (! empty($info['dibatalkan'])) {
                    $baris[] = "{$info['dibatalkan']} setoran Anda yang belum diterima dibatalkan otomatis. Anda dapat menyetor ke bank sampah lain.";
                }
                if ($s > 0) {
                    $baris[] = 'Saldo Anda di bank sampah ini dapat dicairkan hingga '.$batas->translatedFormat('d F Y').'.';
                }
            } else {
                $judul = "Bank sampah {$bank->nama} dihentikan";
                $baris[] = 'Bank sampah ini telah dihentikan dan tidak lagi menerima setoran.';
                if (! empty($info['dibatalkan'])) {
                    $baris[] = "{$info['dibatalkan']} setoran Anda yang belum diterima dibatalkan otomatis.";
                }
                if ($s > 0) {
                    $baris[] = 'Saldo Anda tetap tercatat dan dapat dilihat pada Beranda. Sisa saldo ditindaklanjuti Admin bersama pengelola bank sampah.';
                }
            }
            if ($s > 0) {
                $baris[] = 'Saldo Anda di bank sampah ini: '.SetoranService::rp($s).'.';
            }
            $baris[] = $kontak;

            Notifier::user($c, $judul, implode(' ', $baris), route('customer.beranda'), 'peringatan');
            EmailService::kirimKeUser($c, 'bank_dihentikan', $judul, $baris, 'Buka SIRKULA', route('customer.beranda'));
        });
    }

    // ------------------------------------------------------------- tugas terjadwal

    /** Setoran Ditunda yang melewati batas hari dibatalkan otomatis. */
    public function batalkanDitundaLewatBatas(): int
    {
        $batas = now()->subDays(Pengaturan::get('ditunda_batas_hari'));
        $jumlah = 0;

        Setoran::where('status', 'ditunda')->where('ditunda_pada', '<=', $batas)->get()->each(function (Setoran $s) use (&$jumlah) {
            DB::transaction(function () use ($s) {
                $s->update(['status' => 'dibatalkan', 'dibatalkan_oleh' => 'sistem', 'alasan_penolakan' => 'Tertunda terlalu lama karena bank sampah ditangguhkan.']);
                AuditLogger::catat('setoran', $s->id, 'status', ['status' => 'ditunda'], ['status' => 'dibatalkan', 'oleh' => 'sistem']);
                $c = $s->customer;
                Notifier::user($c, 'Setoran dibatalkan otomatis', "Setoran #{$s->id} dibatalkan karena bank sampah masih ditangguhkan melewati batas waktu.", route('customer.setoran.show', $s->id), 'peringatan');
                EmailService::kirimKeUser($c, 'notifikasi_customer', 'Setoran dibatalkan otomatis', ["Setoran #{$s->id} dibatalkan karena bank sampah masih ditangguhkan melewati batas waktu. Anda dapat menyetor ke bank sampah lain."], 'Lihat setoran', route('customer.setoran.show', $s->id));
            });
            $jumlah++;
        });

        return $jumlah;
    }

    /**
     * Dalam penyelesaian -> Nonaktif setelah masa berakhir, hanya bila tidak ada setoran belum final
     * dan saldo belum dicairkan nol. Jika tidak, status tetap dan Admin diingatkan.
     */
    public function selesaikanOtomatis(): array
    {
        $hasil = ['nonaktif' => 0, 'diingatkan' => 0];

        User::where('role', User::ROLE_SUPPLIER)->where('status_akun', 'dalam_penyelesaian')->where('batas_penyelesaian', '<=', now())->get()
            ->each(function (User $s) use (&$hasil) {
                $d = $this->dampak($s->bank_sampah_id);
                if ($d['setoran_belum_final'] === 0 && $d['saldo_belum_dicairkan'] <= 0) {
                    $this->ubah(null, $s, 'nonaktif', 'Masa penyelesaian berakhir dan seluruh kewajiban telah selesai.');
                    $hasil['nonaktif']++;

                    return;
                }
                if (Cache::add("penyelesaian-ingat:{$s->id}", 1, now()->addDay())) {
                    Notifier::admin('supplier.status', 'Masa penyelesaian berakhir', "{$s->bankSampah->nama}: masih ada {$d['setoran_belum_final']} setoran belum final atau saldo ".SetoranService::rp($d['saldo_belum_dicairkan']).'. Status tetap Dalam penyelesaian.', route('admin.supplier.show', $s->id), 'peringatan');
                    $hasil['diingatkan']++;
                }
            });

        return $hasil;
    }
}
