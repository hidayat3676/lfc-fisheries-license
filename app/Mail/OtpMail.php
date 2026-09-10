<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose,
        public int $ttlMinutes,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->purpose) {
            'register' => 'Your registration verification code',
            'reset' => 'Your password reset code',
            'login' => 'Your login verification code',
            default => 'Your verification code',
        };

        return new Envelope(subject: $subject.' — '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.otp-text',
            with: [
                'code' => $this->code,
                'purpose' => $this->purpose,
                'ttlMinutes' => $this->ttlMinutes,
            ],
        );
    }
}
