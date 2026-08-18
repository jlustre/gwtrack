<?php

return [
    'ai_coaching' => [
        'enabled' => env('CFM_AI_COACHING_ENABLED', true),
        'max_sessions_per_trainee' => 50,
        'max_question_length' => 500,
    ],

    'sms' => [
        'enabled' => env('CFM_SMS_ENABLED', false),
        'driver' => env('CFM_SMS_DRIVER', 'log'),
        'from' => env('CFM_SMS_FROM', 'GWTrack'),
    ],

    'sms_templates' => [
        'check_in' => [
            'label' => 'Coaching check-in',
            'subject' => 'Coaching check-in from your CFM',
            'body' => 'Hi {trainee}, this is {cfm}. I wanted to check in on your progress this week. Please log into GWTrack and review your next steps.',
        ],
        'meeting_reminder' => [
            'label' => 'Meeting reminder',
            'subject' => 'Upcoming coaching session',
            'body' => 'Hi {trainee}, reminder to connect with your CFM {cfm} for your upcoming coaching session. Reply if you need to reschedule.',
        ],
        'progress_nudge' => [
            'label' => 'Progress nudge',
            'subject' => 'Keep your momentum going',
            'body' => 'Hi {trainee}, great work so far. Focus on your licensing and FAP milestones this week — your CFM {cfm} is here to help.',
        ],
        'custom' => [
            'label' => 'Custom message',
            'subject' => 'Message from your CFM',
            'body' => '',
        ],
    ],
];
