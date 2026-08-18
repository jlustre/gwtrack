<?php

namespace App\Jobs\Communication;

use App\Mail\CommunicationNewsletterMail;
use App\Models\AnnouncementNewsletter;
use App\Models\User;
use App\Services\Communication\AnnouncementAudienceResolver;
use App\Services\Communication\NewsletterGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewsletterJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $newsletterId,
        public int $senderId,
        public string $audienceType = 'all',
    ) {}

    public function handle(
        NewsletterGeneratorService $newsletters,
        AnnouncementAudienceResolver $audience,
    ): void {
        $newsletter = AnnouncementNewsletter::query()->find($this->newsletterId);
        $sender = User::query()->find($this->senderId);

        if (! $newsletter || ! $sender) {
            return;
        }

        $recipientIds = $audience->resolve($this->audienceType)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $sent = 0;

        foreach (array_chunk($recipientIds, 50) as $chunk) {
            $users = User::query()->whereIn('id', $chunk)->whereNotNull('email')->get();

            foreach ($users as $user) {
                Mail::to($user)->queue(new CommunicationNewsletterMail($user, $newsletter));
                $sent++;
            }
        }

        $newsletters->markSent($newsletter, $sent);
    }
}
