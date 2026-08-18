<?php

namespace Tests\Support;

use App\Models\AnnouncementCategory;
use App\Models\AnnouncementTemplate;
use App\Models\User;
use App\Support\AnnouncementCategoryCatalog;

class AnnouncementTestFixtures
{
    public static function seedCategories(): void
    {
        AnnouncementCategoryCatalog::seed();
    }

    public static function seedTemplates(?User $author = null): void
    {
        self::seedCategories();

        $author ??= User::query()->orderBy('id')->first();
        $categories = AnnouncementCategory::query()->pluck('id', 'code');

        foreach (self::templateDefinitions($categories->all()) as $template) {
            AnnouncementTemplate::query()->updateOrCreate(
                ['code' => $template['code']],
                array_merge($template, [
                    'prompt_hint' => 'Use professional, encouraging tone suitable for a financial services agency.',
                    'default_audience_type' => 'all',
                    'is_active' => true,
                    'created_by' => $author?->id,
                ]),
            );
        }
    }

    /**
     * @param  array<string, int>  $categories
     * @return list<array<string, mixed>>
     */
    private static function templateDefinitions(array $categories): array
    {
        return [
            [
                'code' => 'general-update',
                'name' => 'General company update',
                'template_type' => 'announcement',
                'category_id' => $categories['general'] ?? null,
                'title_template' => '{{topic}}',
                'summary_template' => 'Important update for all associates regarding {{topic}}.',
                'body_template' => "Team,\n\nWe are sharing an important update about {{topic}}.\n\nPlease review the details in the Communication Hub and reach out to your leader with any questions.\n\nThank you,\n{{author_name}}",
                'default_priority' => 'important',
            ],
            [
                'code' => 'leadership-weekly',
                'name' => 'Weekly leadership message',
                'template_type' => 'leadership_message',
                'category_id' => $categories['leadership'] ?? null,
                'title_template' => 'Weekly leadership message: {{topic}}',
                'summary_template' => 'Leadership priorities and encouragement for the week ahead.',
                'body_template' => "Team,\n\nThis week we are focused on {{topic}}. Stay consistent with field activity, support your teammates, and celebrate progress along the way.\n\n— Leadership",
                'default_priority' => 'important',
            ],
            [
                'code' => 'event-summary',
                'name' => 'Event summary',
                'template_type' => 'event_summary',
                'category_id' => $categories['event'] ?? null,
                'title_template' => 'Upcoming: {{topic}}',
                'summary_template' => 'Join us for an upcoming agency event.',
                'body_template' => "We are excited to announce {{topic}}.\n\nSave the date, review the details in the hub, and RSVP so we can plan accordingly.",
                'default_priority' => 'important',
            ],
            [
                'code' => 'campaign-update',
                'name' => 'Campaign update',
                'template_type' => 'campaign_update',
                'category_id' => $categories['campaign'] ?? null,
                'title_template' => 'Campaign update: {{topic}}',
                'summary_template' => 'Latest standings and reminders for the active campaign.',
                'body_template' => "The {{topic}} campaign is underway.\n\nCheck the Campaign Center for leaderboard standings, rules, and prizes. Keep pushing — every activity counts.",
                'default_priority' => 'high',
            ],
            [
                'code' => 'newsletter-intro-weekly',
                'name' => 'Weekly newsletter intro',
                'template_type' => 'newsletter_intro',
                'title_template' => 'Weekly digest',
                'summary_template' => null,
                'body_template' => "Welcome to your {{period_label}} digest from {{organization}}.\n\nThis edition highlights {{item_count}} updates across leadership messages, recognition, events, and campaigns. Read on for what matters most this week.",
                'default_priority' => 'informational',
            ],
        ];
    }
}
