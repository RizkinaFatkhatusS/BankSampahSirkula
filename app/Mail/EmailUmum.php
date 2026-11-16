<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Email transaksional SIRKULA. Tidak pernah memuat kata sandi; hanya tautan sekali pakai (Matriks notifikasi). */
class EmailUmum extends Mailable
{
    public function __construct(
        public string $subjek,
        public array $baris,
        public ?string $tombolTeks = null,
        public ?string $tombolUrl = null,
        public ?string $catatan = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[SIRKULA] '.$this->subjek);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.umum');
    }
}
