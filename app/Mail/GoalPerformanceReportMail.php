<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class GoalPerformanceReportMail extends Mailable
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
        $label = $this->reportData['period_label'] ?? 'Performance';

        return new Envelope(
            subject: "{$label} Goal Performance Report — GWTracks",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.goals.performance-report',
            with: [
                'user' => $this->user,
                'report' => $this->reportData,
            ],
        );
    }
}
