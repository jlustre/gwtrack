<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\Communication\CommunicationHubService;
use App\Support\AnnouncementCategoryCatalog;
use Illuminate\Database\Seeder;

class CommunicationHubDemoSeeder extends Seeder
{
    public function run(): void
    {
        AnnouncementCategoryCatalog::seed();
        $this->call(RecognitionBadgeSeeder::class);

        $author = User::query()->whereNull('deleted_at')->orderBy('id')->first();
        $honoree = User::query()->whereNull('deleted_at')->where('id', '!=', $author?->id)->orderBy('id')->skip(1)->first()
            ?? User::query()->whereNull('deleted_at')->orderBy('id')->skip(1)->first();

        if (! $author) {
            $this->command?->warn('CommunicationHubDemoSeeder skipped: no users available.');

            return;
        }

        $hub = app(CommunicationHubService::class);
        $recognition = app(\App\Services\Communication\RecognitionService::class);

        $samples = [
            
        ];

        foreach ($samples as $sample) {
            $existing = \App\Models\MessageCenterAnnouncement::query()
                ->where('title', $sample['title'])
                ->first();

            if ($existing) {
                continue;
            }

            $announcement = $hub->createDraft($sample, $author);
            $hub->publish($announcement);
        }

        if ($honoree && $author) {
            $existingRecognition = \App\Models\MessageCenterAnnouncement::query()
                ->where('title', 'like', '%'.$honoree->name.'%')
                ->whereHas('category', fn ($q) => $q->where('code', 'recognition'))
                ->exists();

            if (! $existingRecognition) {
                $badgeId = \App\Models\Badge::query()->where('slug', 'new-license')->value('id');
                $rendered = $recognition->renderTemplate('new_license', $honoree);
                $post = $recognition->createRecognitionPost([
                    'recognition_type' => 'new_license',
                    'honoree_user_id' => $honoree->id,
                    'title' => $rendered['title'],
                    'summary' => $rendered['summary'],
                    'body' => $rendered['body'],
                    'badge_id' => $badgeId,
                    'is_featured' => true,
                ], $author);
                $recognition->publishRecognition($post, $author);
            }
        }

        $this->command?->info('CommunicationHubDemoSeeder published sample announcements.');

        $campaigns = app(\App\Services\Communication\CampaignService::class);
        $existingCampaign = \App\Models\AnnouncementCampaign::query()->where('code', 'demo-production-challenge')->first();

        if (! $existingCampaign && $author) {
            $campaign = $campaigns->createCampaign([
                'code' => 'demo-production-challenge',
                'name' => 'Demo Production Challenge',
                'type' => 'production',
                'description' => 'Sample campaign for local testing.',
                'rules' => 'Posted AP counts toward your score during the campaign window.',
                'prizes' => ['Top producer recognition', 'Agency shout-out'],
                'starts_at' => now()->subWeek(),
                'ends_at' => now()->addMonth(),
            ], $author);

            $existing = \App\Models\MessageCenterAnnouncement::query()->where('title', 'Demo Production Challenge is now live')->first();
            if (! $existing) {
                $announcement = $hub->createDraft([
                    'category_code' => 'campaign',
                    'title' => 'Demo Production Challenge is now live',
                    'summary' => $campaign->description,
                    'body' => $campaign->rules,
                    'priority' => 'high',
                    'audience_type' => 'all',
                    'campaign_id' => $campaign->id,
                ], $author);
                $hub->publish($announcement);
            }
        }
    }
}

