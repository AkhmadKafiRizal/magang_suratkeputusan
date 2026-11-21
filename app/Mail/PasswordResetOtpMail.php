<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $requestedAt;

    public function __construct(public readonly string $otp)
    {
        $this->requestedAt = now()->format('H:i');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Kode Verifikasi {$this->requestedAt} | SIARKA Bapenda Jember");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-otp',
            text: 'emails.password-reset-otp-text',
        );
    }
}
