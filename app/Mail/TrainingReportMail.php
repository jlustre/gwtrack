<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TrainingReportMail extends Mailable
{
    /**
     * @param  array<string, mixed>  $reportData
     */
    public function __construct(
        public User $user,
        public array $reportData,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->reportData['period_label'] ?? 'Training';

        return new Envelope(
            subject: "{$label} Training Report — GWTracks Academy",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.training.report',
            with: [
                'user' => $this->user,
                'report' => $this->reportData,
            ],
        );
    }
}
