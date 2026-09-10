<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Application $application,
        public string $event,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->event) {
            'submitted' => 'Application received — '.$this->application->application_no,
            'approved' => 'Licence issued — '.$this->application->application_no,
            'rejected' => 'Application rejected — '.$this->application->application_no,
            'info_required' => 'More information required — '.$this->application->application_no,
            default => 'Application update — '.$this->application->application_no,
        };

        return new Envelope(subject: $subject.' | '.config('app.name'));
    }

    public function content(): Content
    {
        return new Content(
            text: 'emails.application-status-text',
            with: [
                'application' => $this->application,
                'event' => $this->event,
                'license' => $this->application->license,
            ],
        );
    }
}
