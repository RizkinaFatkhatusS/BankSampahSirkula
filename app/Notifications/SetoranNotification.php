<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** Notifikasi database (PRD 5.3): tampil di aplikasi tanpa layanan pihak ketiga. */
class SetoranNotification extends Notification
{
    public function __construct(
        public string $judul,
        public string $pesan,
        public ?int $setoranId = null,
        public string $tipe = 'info',
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'setoran_id' => $this->setoranId,
            'tipe' => $this->tipe,
        ];
    }
}
