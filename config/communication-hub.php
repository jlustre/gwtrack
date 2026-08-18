<?php

return [

    'priorities' => [
        'informational' => ['label' => 'Informational', 'color' => '#64748B', 'notification' => 'info'],
        'important' => ['label' => 'Important', 'color' => '#84BB41', 'notification' => 'medium'],
        'high' => ['label' => 'High Priority', 'color' => '#F59E0B', 'notification' => 'high'],
        'critical' => ['label' => 'Critical', 'color' => '#DC2626', 'notification' => 'critical'],
        'emergency' => ['label' => 'Emergency', 'color' => '#991B1B', 'notification' => 'critical'],
    ],

    'statuses' => ['draft', 'scheduled', 'published', 'archived'],

    'audience_types' => [
        'all' => 'All Users',
        'organization' => 'Entire Organization',
        'roles' => 'Specific Roles',
        'teams' => 'Specific Teams',
        'ranks' => 'Specific Ranks',
        'users' => 'Specific Users',
    ],

    'legacy_audience_aliases' => [
        'organization' => 'all',
    ],

    'reactions' => [
        'like' => ['label' => 'Like', 'icon' => '👍'],
        'celebrate' => ['label' => 'Celebrate', 'icon' => '🎉'],
        'support' => ['label' => 'Support', 'icon' => '💪'],
        'insightful' => ['label' => 'Insightful', 'icon' => '💡'],
    ],

    'critical_banner_priorities' => ['critical', 'emergency'],

    'recognition_templates' => [
        'new_recruit' => [
            'label' => 'New Recruit',
            'badge_slug' => 'new-recruit',
            'title' => 'Welcome {{honoree_name}} to the team!',
            'summary' => 'Join us in welcoming a new recruit to GWTrack.',
            'body' => "Please join us in welcoming {{honoree_name}} to the organization.\n\nTheir energy and commitment are already making an impact. Reach out, introduce yourself, and help them succeed in onboarding and licensing.\n\nCongratulations, {{honoree_name}} — we are glad you are here.",
        ],
        'new_license' => [
            'label' => 'New License',
            'badge_slug' => 'new-license',
            'title' => '{{honoree_name}} earned a new license!',
            'summary' => 'Celebrate a licensing milestone achieved through dedication and discipline.',
            'body' => "Congratulations to {{honoree_name}} for earning a new license.\n\nThis milestone reflects consistent effort, study, and professional growth. Well done — the team is proud of you.",
        ],
        'first_sale' => [
            'label' => 'First Sale',
            'badge_slug' => 'first-sale',
            'title' => '{{honoree_name}} closed their first sale!',
            'summary' => 'A major production milestone worth celebrating.',
            'body' => "Huge congratulations to {{honoree_name}} on closing their first sale.\n\nThis is the beginning of a strong production journey. Keep building momentum and serving clients with excellence.",
        ],
        'promotion' => [
            'label' => 'Promotion',
            'badge_slug' => 'promotion',
            'title' => '{{honoree_name}} has been promoted!',
            'summary' => 'Leadership recognition for rank or role advancement.',
            'body' => "Please congratulate {{honoree_name}} on their promotion.\n\nTheir leadership, results, and example for the team made this advancement well deserved.",
        ],
        'fap_completion' => [
            'label' => 'FAP Completion',
            'badge_slug' => 'fap-graduate',
            'title' => '{{honoree_name}} completed the Field Apprenticeship Program!',
            'summary' => 'Celebrate a graduate of the FAP journey.',
            'body' => "{{honoree_name}} has completed the Field Apprenticeship Program.\n\nThank you to every mentor and leader who supported this milestone. {{honoree_name}}, excellent work — onward to licensing and production excellence.",
        ],
        'top_producer' => [
            'label' => 'Top Producer',
            'badge_slug' => 'top-producer',
            'title' => 'Top Producer: {{honoree_name}}',
            'summary' => 'Recognizing outstanding production performance.',
            'body' => "{{honoree_name}} is recognized as a Top Producer this period.\n\nTheir results, consistency, and client focus set the standard for the agency. Congratulations on an outstanding achievement.",
        ],
        'top_recruiter' => [
            'label' => 'Top Recruiter',
            'badge_slug' => 'top-recruiter',
            'title' => 'Top Recruiter: {{honoree_name}}',
            'summary' => 'Recognizing exceptional recruiting performance.',
            'body' => "{{honoree_name}} is recognized as a Top Recruiter this period.\n\nThank you for building the team and creating opportunities for new associates to thrive.",
        ],
        'leadership_milestone' => [
            'label' => 'Leadership Milestone',
            'badge_slug' => 'leadership-milestone',
            'title' => 'Leadership milestone: {{honoree_name}}',
            'summary' => 'Celebrating a leadership achievement.',
            'body' => "{{honoree_name}} has reached an important leadership milestone.\n\nTheir influence, coaching, and commitment to team success continue to elevate the organization.",
        ],
    ],

    'leadership_desk' => [
        'category_code' => 'leadership',
        'signature_prefix' => '— Leadership',
    ],

    'campaign_types' => [
        'recruiting' => ['label' => 'Recruiting Challenge', 'metric' => 'recruiting', 'unit' => 'recruits'],
        'production' => ['label' => 'Production Challenge', 'metric' => 'production', 'unit' => 'AP'],
        'licensing' => ['label' => 'Licensing Challenge', 'metric' => 'licensing', 'unit' => 'milestones'],
        'training' => ['label' => 'Training Challenge', 'metric' => 'training', 'unit' => 'lessons'],
    ],

    'event_defaults' => [
        'visibility' => 'organization',
        'duration_hours' => 1,
    ],

    'broadcast_audience_aliases' => [
        'agency' => 'all',
    ],

    'newsletter_periods' => [
        'weekly' => ['label' => 'Weekly Digest', 'days' => 7],
        'monthly' => ['label' => 'Monthly Roundup', 'days' => 30],
        'quarterly' => ['label' => 'Quarterly Review', 'days' => 90],
    ],

    'newsletter_sections' => [
        'leadership' => ['label' => 'Leadership Messages'],
        'announcements' => ['label' => 'Announcements & Updates'],
        'recognition' => ['label' => 'Recognition'],
        'events' => ['label' => 'Events & Webinars'],
        'campaigns' => ['label' => 'Campaigns & Challenges'],
    ],

    'ai' => [
        'enabled' => env('COMMUNICATION_AI_ENABLED', true),
        'use_llm' => env('COMMUNICATION_AI_USE_LLM', false),
        'model' => env('COMMUNICATION_AI_MODEL', 'gpt-4o-mini'),
        'features' => [
            'announcement_draft' => true,
            'recognition_draft' => true,
            'event_summary' => true,
            'newsletter_intro' => true,
            'leadership_message' => true,
            'campaign_update' => true,
        ],
        'draft_types' => [
            'announcement' => 'Announcement draft',
            'leadership_message' => 'Leadership message',
            'event_summary' => 'Event summary',
            'campaign_update' => 'Campaign update',
            'newsletter_intro' => 'Newsletter intro',
        ],
        'draft_templates' => [
            'announcement' => [
                'title' => '{{topic}}',
                'summary' => 'Update regarding {{topic}}.',
                'body' => "Team,\n\nPlease review this update about {{topic}}.\n\nThank you,\n{{author_name}}",
            ],
            'leadership_message' => [
                'title' => 'Leadership message: {{topic}}',
                'summary' => 'A message from leadership for the team.',
                'body' => "Team,\n\nThis week we are emphasizing {{topic}}. Stay focused, support one another, and keep building momentum.\n\n— Leadership",
            ],
            'event_summary' => [
                'title' => 'Event: {{topic}}',
                'summary' => 'Details for an upcoming agency event.',
                'body' => "Join us for {{topic}}. Review the event announcement in the Communication Hub and RSVP if registration is required.",
            ],
            'campaign_update' => [
                'title' => 'Campaign update: {{topic}}',
                'summary' => 'Progress update for an active campaign.',
                'body' => "The {{topic}} campaign continues. Visit the Campaign Center for standings, rules, and next steps.",
            ],
            'newsletter_intro' => [
                'title' => 'Newsletter intro',
                'summary' => null,
                'body' => "Welcome to your {{period_label}} update from {{organization}}.\n\nInside this edition you'll find {{item_count}} highlights from across the agency.",
            ],
        ],
        'prompts' => [
            'announcement' => 'Write a professional internal announcement draft.',
            'leadership_message' => 'Write an encouraging leadership message for insurance associates.',
            'event_summary' => 'Write a concise event announcement summary.',
            'campaign_update' => 'Write a motivating campaign progress update.',
            'newsletter_intro' => 'Write a short newsletter introduction paragraph.',
        ],
    ],

];
