<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GoogleVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $code) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Kode verifikasi akun Ledger');
    }

    public function content(): Content
    {
        $publicUrl = config('services.ledger.url');

        return new Content(
            view: 'emails.google-verification-code',
            text: 'emails.google-verification-code-text',
            with: [
                'code' => $this->code,
                'publicUrl' => $publicUrl,
                'logoUrl' => $publicUrl.'/pwa-192x192.png',
            ],
        );
    }
}
