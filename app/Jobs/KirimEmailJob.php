<?php

namespace App\Jobs;

use App\Mail\EmailUmum;
use App\Models\LogEmail;
use App\Services\Notifier;
use App\Services\Pengaturan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Mengirim satu email dari log_email, dengan percobaan ulang otomatis (FR-46). */
class KirimEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;

    /** Jeda antarpercobaan (detik) */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $logId)
    {
        $this->tries = max(1, Pengaturan::get('email_percobaan'));
    }

    public function handle(): void
    {
        $log = LogEmail::find($this->logId);
        if (! $log || $log->status === 'terkirim') {
            return;
        }
        $m = $log->muatan ?? [];
        $log->increment('percobaan');

        try {
            Mail::to($log->penerima)->send(new EmailUmum($m['subjek'] ?? '', $m['baris'] ?? [], $m['tombol_teks'] ?? null, $m['tombol_url'] ?? null, $m['catatan'] ?? null));
        } catch (Throwable $e) {
            $log->update(['pesan_error' => mb_substr($e->getMessage(), 0, 500)]);
            throw $e;
        }

        $log->update(['status' => 'terkirim', 'dikirim_pada' => now(), 'pesan_error' => null]);
    }

    /** Dipanggil setelah percobaan terakhir gagal: tandai gagal dan beri tahu Admin yang berhak. */
    public function failed(Throwable $e): void
    {
        $log = LogEmail::find($this->logId);
        if (! $log) {
            return;
        }
        $log->update(['status' => 'gagal', 'pesan_error' => mb_substr($e->getMessage(), 0, 500)]);

        Notifier::admin('email.kelola', 'Email gagal terkirim', "Email {$log->jenis} ke {$log->penerima} gagal setelah {$log->percobaan} percobaan.", route('admin.log-email'), 'peringatan');
    }
}
