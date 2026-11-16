<?php

namespace App\Services;

use App\Models\BankSampah;
use App\Models\DokumenPengajuan;
use App\Models\JenisDokumen;
use App\Models\PengajuanSupplier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Pendaftaran, verifikasi, dan penerbitan akun Supplier (FR-35 s.d. FR-38, FR-46). */
class PengajuanService
{
    public const DISK = 'local'; // disk private: dokumen verifikasi tidak punya URL publik

    /** Aturan validasi formulir pengajuan (publik maupun manual oleh Admin). */
    public static function aturan(bool $semuaDokumen = true): array
    {
        $rules = [
            'nama_bank_sampah' => ['required', 'string', 'max:150'],
            'alamat' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'nomor_kontak' => ['required', 'string', 'max:30'],
            'jam_operasional' => ['required', 'string', 'max:100'],
            'nama_penanggung_jawab' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'nomor_hp' => ['required', 'string', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'dokumen' => ['nullable', 'array'],
        ];

        foreach (JenisDokumen::aktif()->get() as $j) {
            $aturan = ['file', 'mimes:'.Pengaturan::formatDokumen(), 'max:'.Pengaturan::maksKb()];
            $rules["dokumen.{$j->id}"] = $semuaDokumen && $j->wajib
                ? ['required', 'array', 'min:'.max(1, $j->min_berkas)]
                : ['nullable', 'array'];
            $rules["dokumen.{$j->id}.*"] = $aturan;
        }

        return $rules;
    }

    public static function pesan(): array
    {
        return [
            'nomor_hp.regex' => 'Nomor HP tidak valid. Contoh: 081234567890.',
            'dokumen.*.required' => 'Dokumen wajib diunggah.',
            'dokumen.*.min' => 'Unggah minimal :min berkas untuk dokumen ini.',
            'dokumen.*.*.mimes' => 'Format berkas harus: :values.',
            'dokumen.*.*.max' => 'Ukuran berkas maksimal :max KB.',
            'dokumen.*.*.uploaded' => 'Berkas gagal diunggah. Periksa ukuran dan format berkas.',
        ];
    }

    private static function kodeBaru(): string
    {
        $abjad = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $kode = 'SRK-';
            for ($i = 0; $i < 8; $i++) {
                $kode .= $abjad[random_int(0, strlen($abjad) - 1)];
            }
        } while (PengajuanSupplier::where('kode_pengajuan', $kode)->exists());

        return $kode;
    }

    /** Email tidak boleh dipakai pengajuan yang masih diproses atau akun yang sudah ada. */
    public static function pastikanEmailBebas(string $email, ?int $kecualiPengajuan = null): void
    {
        $email = strtolower($email);
        $aktif = PengajuanSupplier::whereRaw('lower(email) = ?', [$email])->whereIn('status', PengajuanSupplier::STATUS_AKTIF)
            ->when($kecualiPengajuan, fn ($q) => $q->where('id', '!=', $kecualiPengajuan))->exists();
        $disetujui = PengajuanSupplier::whereRaw('lower(email) = ?', [$email])->where('status', 'disetujui')->exists();

        if ($aktif) {
            throw ValidationException::withMessages(['email' => 'Email ini masih memiliki pengajuan yang sedang diproses. Cek statusnya melalui halaman Cek Status Pengajuan.']);
        }
        if ($disetujui || User::whereRaw('lower(email) = ?', [$email])->exists()) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah terdaftar. Gunakan email lain atau masuk ke akun Anda.']);
        }
    }

    /** @param array<int,array<int,UploadedFile>> $berkas jenis_dokumen_id => [file...] */
    public function buat(array $data, array $berkas, ?User $admin = null): PengajuanSupplier
    {
        self::pastikanEmailBebas($data['email']);

        return DB::transaction(function () use ($data, $berkas, $admin) {
            $p = PengajuanSupplier::create([
                'kode_pengajuan' => self::kodeBaru(),
                'token_status' => Str::random(48),
                'nama_bank_sampah' => $data['nama_bank_sampah'],
                'alamat' => $data['alamat'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'nomor_kontak' => $data['nomor_kontak'],
                'jam_operasional' => $data['jam_operasional'],
                'nama_penanggung_jawab' => $data['nama_penanggung_jawab'],
                'email' => strtolower($data['email']),
                'nomor_hp' => $data['nomor_hp'],
                'status' => 'menunggu_verifikasi',
                'dibuat_oleh_admin_id' => $admin?->id,
            ]);

            foreach ($berkas as $jenisId => $files) {
                foreach ((array) $files as $file) {
                    $this->simpanDokumen($p, (int) $jenisId, $file);
                }
            }

            $this->riwayat($p, 'menunggu_verifikasi', $admin ? 'Pengajuan dibuat oleh Admin atas nama calon Supplier.' : 'Pengajuan dikirim.', $admin?->id);
            AuditLogger::catat('pengajuan_supplier', $p->id, 'dibuat', null, ['kode' => $p->kode_pengajuan, 'manual' => (bool) $admin]);

            Notifier::admin('pengajuan.putuskan', 'Pengajuan Supplier baru', "{$p->nama_bank_sampah} ({$p->kode_pengajuan}) menunggu verifikasi.", route('admin.pengajuan.show', $p->id), 'info');

            EmailService::kirim(
                'pengajuan_diterima',
                $p->email,
                'Pengajuan pendaftaran Supplier diterima',
                ["Halo {$p->nama_penanggung_jawab}, pengajuan pendaftaran bank sampah \"{$p->nama_bank_sampah}\" sudah kami terima.", "Kode pengajuan Anda: {$p->kode_pengajuan}. Simpan kode ini bersama email Anda untuk mengecek status pengajuan.", 'Pengajuan akan diperiksa oleh Admin. Kami akan mengabari Anda melalui email.'],
                'Cek status pengajuan',
                $p->tautanStatus(),
                null,
                null,
                $p->id,
            );

            return $p;
        });
    }

    private function simpanDokumen(PengajuanSupplier $p, int $jenisId, UploadedFile $file): void
    {
        $path = Storage::disk(self::DISK)->putFileAs("pengajuan/{$p->id}", $file, Str::uuid().'.'.$file->getClientOriginalExtension());

        DokumenPengajuan::create([
            'pengajuan_id' => $p->id,
            'jenis_dokumen_id' => $jenisId,
            'path_berkas' => $path,
            'nama_asli' => $file->getClientOriginalName(),
            'ukuran' => $file->getSize(),
            'status_pemeriksaan' => 'belum',
        ]);
    }

    public function riwayat(PengajuanSupplier $p, string $status, ?string $catatan = null, ?int $oleh = null): void
    {
        $p->riwayat()->create(['status' => $status, 'catatan' => $catatan, 'oleh' => $oleh, 'waktu' => now()]);
    }

    /** Calon Supplier memperbaiki hanya bagian yang diminta revisi lalu mengirim ulang. */
    public function kirimUlang(PengajuanSupplier $p, array $data, array $berkas): PengajuanSupplier
    {
        if ($p->status !== 'perlu_revisi') {
            throw ValidationException::withMessages(['status' => 'Pengajuan ini tidak sedang diminta revisi.']);
        }
        self::pastikanEmailBebas($data['email'], $p->id);

        return DB::transaction(function () use ($p, $data, $berkas) {
            $p->update([
                'nama_bank_sampah' => $data['nama_bank_sampah'], 'alamat' => $data['alamat'],
                'latitude' => $data['latitude'], 'longitude' => $data['longitude'],
                'nomor_kontak' => $data['nomor_kontak'], 'jam_operasional' => $data['jam_operasional'],
                'nama_penanggung_jawab' => $data['nama_penanggung_jawab'], 'nomor_hp' => $data['nomor_hp'],
                'email' => strtolower($data['email']),
            ]);

            // Hanya dokumen yang ditandai "perlu diperbaiki" yang boleh diganti
            $boleh = $p->dokumen()->where('status_pemeriksaan', 'perlu_diperbaiki')->pluck('jenis_dokumen_id')->unique()->all();
            foreach ($berkas as $jenisId => $files) {
                $files = array_filter((array) $files);
                if (! $files || ! in_array((int) $jenisId, $boleh, true)) {
                    continue;
                }
                foreach ($p->dokumen()->where('jenis_dokumen_id', $jenisId)->where('status_pemeriksaan', 'perlu_diperbaiki')->get() as $lama) {
                    Storage::disk(self::DISK)->delete($lama->path_berkas);
                    $lama->delete();
                }
                foreach ($files as $file) {
                    $this->simpanDokumen($p, (int) $jenisId, $file);
                }
            }

            // Semua dokumen yang masih ditandai perlu diperbaiki harus sudah diganti
            if ($p->dokumen()->where('status_pemeriksaan', 'perlu_diperbaiki')->exists()) {
                throw ValidationException::withMessages(['dokumen' => 'Masih ada dokumen yang diminta diperbaiki. Unggah berkas pengganti untuk dokumen tersebut.']);
            }

            $p->update(['status' => 'menunggu_verifikasi', 'ditangani_oleh' => null]);
            $this->riwayat($p, 'menunggu_verifikasi', 'Pengajuan diperbaiki dan dikirim ulang.');
            AuditLogger::catat('pengajuan_supplier', $p->id, 'dikirim_ulang', ['status' => 'perlu_revisi'], ['status' => 'menunggu_verifikasi']);
            Notifier::admin('pengajuan.putuskan', 'Revisi pengajuan dikirim ulang', "{$p->nama_bank_sampah} ({$p->kode_pengajuan}) sudah diperbaiki.", route('admin.pengajuan.show', $p->id), 'info');

            return $p;
        });
    }

    // ------------------------------------------------------------- tindakan Admin

    public function mulai(PengajuanSupplier $p, User $admin): void
    {
        if ($p->status !== 'menunggu_verifikasi') {
            throw ValidationException::withMessages(['status' => 'Pengajuan ini tidak sedang menunggu verifikasi.']);
        }
        $this->tolakPembuat($p, $admin);

        DB::transaction(function () use ($p, $admin) {
            $p->update(['status' => 'sedang_diverifikasi', 'ditangani_oleh' => $admin->id]);
            $this->riwayat($p, 'sedang_diverifikasi', "Diperiksa oleh {$admin->nama}.", $admin->id);
            AuditLogger::catat('pengajuan_supplier', $p->id, 'mulai_verifikasi', ['status' => 'menunggu_verifikasi'], ['status' => 'sedang_diverifikasi', 'admin' => $admin->id]);
        });
    }

    private function tolakPembuat(PengajuanSupplier $p, User $admin): void
    {
        // Prinsip dua pasang mata (FR-38)
        if ($p->dibuat_oleh_admin_id === $admin->id) {
            throw ValidationException::withMessages(['status' => 'Anda tidak dapat memeriksa atau memutuskan pengajuan manual yang Anda buat sendiri. Minta Admin lain melakukannya.']);
        }
    }

    public function periksaDokumen(PengajuanSupplier $p, User $admin, DokumenPengajuan $dok, string $status, ?string $catatan): void
    {
        $this->pastikanPenangan($p, $admin);
        $sebelum = $dok->status_pemeriksaan;
        $dok->update(['status_pemeriksaan' => $status, 'catatan' => $catatan]);
        AuditLogger::catat('dokumen_pengajuan', $dok->id, 'periksa', ['status' => $sebelum], ['status' => $status], $catatan);
    }

    public function catatanVerifikasi(PengajuanSupplier $p, User $admin, ?string $catatan): void
    {
        $this->pastikanPenangan($p, $admin);
        $p->update(['catatan_verifikasi' => $catatan]);
        AuditLogger::catat('pengajuan_supplier', $p->id, 'catatan_verifikasi', null, ['catatan' => $catatan]);
    }

    private function pastikanPenangan(PengajuanSupplier $p, User $admin): void
    {
        if ($p->status !== 'sedang_diverifikasi') {
            throw ValidationException::withMessages(['status' => 'Mulai verifikasi terlebih dahulu.']);
        }
        if ($p->ditangani_oleh !== $admin->id) {
            throw ValidationException::withMessages(['status' => 'Pengajuan ini sedang ditangani Admin lain. Hanya Admin yang menanganinya yang dapat memutuskan, kecuali dialihkan.']);
        }
        $this->tolakPembuat($p, $admin);
    }

    /** @param 'setujui'|'revisi'|'tolak' $keputusan */
    public function putuskan(PengajuanSupplier $p, User $admin, string $keputusan, ?string $catatan): PengajuanSupplier
    {
        $this->pastikanPenangan($p, $admin);

        if (in_array($keputusan, ['revisi', 'tolak'], true) && blank($catatan)) {
            throw ValidationException::withMessages(['catatan' => $keputusan === 'revisi' ? 'Catatan revisi wajib diisi.' : 'Alasan penolakan wajib diisi.']);
        }

        return DB::transaction(function () use ($p, $admin, $keputusan, $catatan) {
            $sebelum = ['status' => $p->status];

            if ($keputusan === 'setujui') {
                if ($p->dokumen()->where('status_pemeriksaan', 'perlu_diperbaiki')->exists()) {
                    throw ValidationException::withMessages(['catatan' => 'Masih ada dokumen yang ditandai perlu diperbaiki. Minta revisi atau ubah hasil pemeriksaannya.']);
                }
                $this->setujui($p, $admin, $catatan);
            } elseif ($keputusan === 'revisi') {
                $p->update(['status' => 'perlu_revisi', 'catatan_admin' => $catatan, 'ditangani_oleh' => null, 'diputuskan_oleh' => $admin->id, 'diputuskan_pada' => now()]);
                $this->riwayat($p, 'perlu_revisi', $catatan, $admin->id);
                EmailService::kirim('pengajuan_revisi', $p->email, 'Pengajuan Anda perlu diperbaiki', ["Halo {$p->nama_penanggung_jawab}, pengajuan Anda belum dapat disetujui dan perlu diperbaiki.", "Catatan Admin: {$catatan}", 'Perbaiki bagian yang diminta melalui halaman Cek Status Pengajuan, lalu kirim ulang. Pengajuan yang tidak diperbaiki dalam '.Pengaturan::get('revisi_kedaluwarsa_hari').' hari akan kedaluwarsa.'], 'Perbaiki pengajuan', $p->tautanStatus(), null, null, $p->id);
            } else {
                $p->update(['status' => 'ditolak', 'catatan_admin' => $catatan, 'ditangani_oleh' => null, 'diputuskan_oleh' => $admin->id, 'diputuskan_pada' => now()]);
                $this->riwayat($p, 'ditolak', $catatan, $admin->id);
                EmailService::kirim('pengajuan_ditolak', $p->email, 'Pengajuan pendaftaran Supplier ditolak', ["Halo {$p->nama_penanggung_jawab}, mohon maaf, pengajuan Anda belum dapat kami setujui.", "Alasan: {$catatan}", 'Anda dapat mengajukan pendaftaran baru setelah melengkapi persyaratan.'], 'Lihat status pengajuan', $p->tautanStatus(), null, null, $p->id);
            }

            AuditLogger::catat('pengajuan_supplier', $p->id, 'keputusan_'.$keputusan, $sebelum, ['status' => $p->fresh()->status], $catatan);

            return $p->fresh();
        });
    }

    /** Persetujuan: membuat akun Supplier (belum aktif) dan bank sampah dalam satu transaksi (FR-37). */
    private function setujui(PengajuanSupplier $p, User $admin, ?string $catatan): void
    {
        if (User::whereRaw('lower(email) = ?', [strtolower($p->email)])->exists()) {
            throw ValidationException::withMessages(['catatan' => 'Email penanggung jawab sudah dipakai akun lain.']);
        }

        $bank = BankSampah::create([
            'nama' => $p->nama_bank_sampah,
            'alamat' => $p->alamat,
            'latitude' => $p->latitude,
            'longitude' => $p->longitude,
            'nomor_kontak' => $p->nomor_kontak,
            'jam_operasional' => $p->jam_operasional,
            'status_tayang' => 'tayang',
        ]);

        $user = User::create([
            'nama' => $p->nama_penanggung_jawab,
            'email' => strtolower($p->email),
            'nomor_hp' => $p->nomor_hp,
            'password' => null,                    // ditetapkan sendiri oleh Supplier melalui tautan aktivasi
            'role' => User::ROLE_SUPPLIER,
            'status_akun' => 'menunggu_aktivasi',
            'bank_sampah_id' => $bank->id,
        ]);

        $p->update(['status' => 'disetujui', 'catatan_admin' => $catatan, 'ditangani_oleh' => null, 'diputuskan_oleh' => $admin->id, 'diputuskan_pada' => now(), 'user_id' => $user->id, 'bank_sampah_id' => $bank->id]);
        $this->riwayat($p, 'disetujui', $catatan ?: 'Pengajuan disetujui. Tautan aktivasi dikirim ke email.', $admin->id);
        AuditLogger::catat('users', $user->id, 'akun_supplier_dibuat', null, ['bank_sampah_id' => $bank->id, 'status_akun' => 'menunggu_aktivasi'], 'Pengajuan '.$p->kode_pengajuan.' disetujui');

        AkunService::kirimAktivasi($user);
    }

    public function alihkan(PengajuanSupplier $p, User $admin, int $targetId): void
    {
        if ($p->status !== 'sedang_diverifikasi') {
            throw ValidationException::withMessages(['target' => 'Hanya pengajuan yang sedang diverifikasi yang dapat dialihkan.']);
        }
        $target = User::whereKey($targetId)->where('status_akun', 'aktif')->whereIn('role', [User::ROLE_ADMIN, User::ROLE_SUPER_ADMIN])->first();
        if (! $target || ! $target->punya('pengajuan.putuskan')) {
            throw ValidationException::withMessages(['target' => 'Admin tujuan tidak memiliki hak akses memutuskan pengajuan.']);
        }
        if ($p->dibuat_oleh_admin_id === $target->id) {
            throw ValidationException::withMessages(['target' => 'Pengajuan manual tidak dapat dialihkan kepada Admin yang membuatnya.']);
        }

        DB::transaction(function () use ($p, $admin, $target) {
            $lama = $p->ditangani_oleh;
            $p->update(['ditangani_oleh' => $target->id]);
            $this->riwayat($p, 'sedang_diverifikasi', "Dialihkan kepada {$target->nama}.", $admin->id);
            AuditLogger::catat('pengajuan_supplier', $p->id, 'alihkan', ['ditangani_oleh' => $lama], ['ditangani_oleh' => $target->id]);
            Notifier::user($target, 'Pengajuan dialihkan kepada Anda', "{$p->nama_bank_sampah} ({$p->kode_pengajuan}) dialihkan oleh {$admin->nama}.", route('admin.pengajuan.show', $p->id));
        });
    }

    /** Dipanggil terjadwal: Perlu revisi yang tidak dikirim ulang menjadi Kedaluwarsa. */
    public function kedaluwarsakan(): int
    {
        $batas = now()->subDays(Pengaturan::get('revisi_kedaluwarsa_hari'));
        $jumlah = 0;

        PengajuanSupplier::where('status', 'perlu_revisi')->get()->each(function (PengajuanSupplier $p) use ($batas, &$jumlah) {
            $terakhir = $p->riwayat()->where('status', 'perlu_revisi')->latest('id')->value('waktu') ?? $p->updated_at;
            if ($terakhir && \Illuminate\Support\Carbon::parse($terakhir)->greaterThan($batas)) {
                return;
            }
            DB::transaction(function () use ($p) {
                $p->update(['status' => 'kedaluwarsa']);
                $this->riwayat($p, 'kedaluwarsa', 'Pengajuan tidak diperbaiki dalam batas waktu.');
                AuditLogger::catat('pengajuan_supplier', $p->id, 'kedaluwarsa', ['status' => 'perlu_revisi'], ['status' => 'kedaluwarsa']);
                EmailService::kirim('pengajuan_kedaluwarsa', $p->email, 'Pengajuan pendaftaran Supplier kedaluwarsa', ["Halo {$p->nama_penanggung_jawab}, pengajuan Anda kedaluwarsa karena tidak diperbaiki dalam batas waktu.", 'Anda dapat mengajukan pendaftaran baru kapan saja.'], 'Lihat status pengajuan', $p->tautanStatus(), null, null, $p->id);
            });
            $jumlah++;
        });

        return $jumlah;
    }

    /** Pengingat Admin untuk pengajuan yang belum diproses melebihi target hari kerja. */
    public function ingatkanAntrean(): int
    {
        $hari = Pengaturan::get('verifikasi_target_hari_kerja');
        $jumlah = 0;

        PengajuanSupplier::where('status', 'menunggu_verifikasi')->get()->each(function (PengajuanSupplier $p) use ($hari, &$jumlah) {
            if ($p->created_at->diffInWeekdays(now()) < $hari) {
                return;
            }
            if ($p->pengingat_terakhir_at && $p->pengingat_terakhir_at->isToday()) {
                return;
            }
            $p->update(['pengingat_terakhir_at' => now()]);
            Notifier::admin('pengajuan.putuskan', 'Pengajuan belum diproses', "{$p->nama_bank_sampah} ({$p->kode_pengajuan}) menunggu lebih dari {$hari} hari kerja.", route('admin.pengajuan.show', $p->id), 'peringatan', true, 'pengingat_pengajuan');
            $jumlah++;
        });

        return $jumlah;
    }
}
