<?php

return [

    'default_channels' => ['in_app'],

    'critical_channels' => ['in_app', 'email', 'push'],

    'critical_sms_priorities' => ['critical'],

    'critical_priorities' => ['urgent', 'critical'],

    'priorities' => [
        'info' => ['label' => 'Info', 'color' => '#64748B'],
        'low' => ['label' => 'Low', 'color' => '#94A3B8'],
        'medium' => ['label' => 'Medium', 'color' => '#84BB41'],
        'high' => ['label' => 'High', 'color' => '#F59E0B'],
        'urgent' => ['label' => 'Urgent', 'color' => '#EA580C'],
        'critical' => ['label' => 'Critical', 'color' => '#DC2626'],
    ],

    'frequencies' => [
        'immediate',
        'daily_digest',
        'weekly_digest',
        'critical_only',
    ],

    'digest' => [
        'daily' => ['enabled' => true, 'default_time' => '07:00'],
        'weekly' => ['enabled' => true, 'default_day' => 1],
    ],

    'escalation' => [
        'cooldown_hours' => 24,
        'max_steps_per_day' => 3,
    ],

    'bell_poll_seconds' => 60,

    'toast_priorities' => ['high', 'urgent', 'critical'],

    'center_tabs' => [
        'all' => ['label' => 'All Notifications', 'type_codes' => []],
        'unread' => ['label' => 'Unread', 'unread' => true],
        'mentorship' => ['label' => 'Mentorship', 'type_codes' => ['mentoring', 'cfm_assignment', 'fap']],
        'training' => ['label' => 'Training', 'type_codes' => ['training']],
        'licensing' => ['label' => 'Licensing', 'type_codes' => ['licensing', 'compliance']],
        'tasks' => ['label' => 'Tasks', 'type_codes' => ['task']],
        'prospects' => ['label' => 'Prospects', 'type_codes' => ['prospect', 'fna']],
        'calendar' => ['label' => 'Calendar', 'type_codes' => ['calendar', 'booking']],
        'goals' => ['label' => 'Goals', 'type_codes' => ['goal', 'production', 'recruiting', 'rank_advancement']],
        'messages' => ['label' => 'Messages', 'type_codes' => ['message']],
        'system' => ['label' => 'System', 'type_codes' => ['system', 'announcement', 'account', 'support_ticket', 'resource', 'recognition', 'risk_alert', 'escalation']],
    ],

    'snooze_options' => [
        '1h' => ['label' => '1 hour', 'hours' => 1],
        '4h' => ['label' => '4 hours', 'hours' => 4],
        'tomorrow' => ['label' => 'Tomorrow morning', 'hours' => null],
    ],

    'queue' => env('NOTIFICATIONS_QUEUE', true),

    'sms' => [
        'enabled' => env('NOTIFICATIONS_SMS_ENABLED', env('APP_ENV') === 'local'),
        'driver' => env('NOTIFICATIONS_SMS_DRIVER', 'log'),
        'from' => env('NOTIFICATIONS_SMS_FROM', 'GWTracks'),
        'max_length' => 160,
        'twilio' => [
            'sid' => env('TWILIO_ACCOUNT_SID'),
            'token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM_NUMBER'),
        ],
    ],

    'push' => [
        'enabled' => env('NOTIFICATIONS_PUSH_ENABLED', env('APP_ENV') === 'local'),
        'driver' => env('NOTIFICATIONS_PUSH_DRIVER', 'log'),
        'vapid' => [
            'subject' => env('NOTIFICATIONS_PUSH_VAPID_SUBJECT', env('APP_URL', 'mailto:admin@gwtracks.com')),
            'public_key' => env('NOTIFICATIONS_PUSH_VAPID_PUBLIC_KEY'),
            'private_key' => env('NOTIFICATIONS_PUSH_VAPID_PRIVATE_KEY'),
        ],
    ],

    'ai' => [
        'insights_enabled' => env('NOTIFICATIONS_AI_INSIGHTS_ENABLED', false),
        'batch_size' => 100,
    ],

    'template_tokens' => [
        'user_name',
        'trainee_name',
        'cfm_name',
        'sponsor_name',
        'agency_owner_name',
        'task_name',
        'course_name',
        'deadline',
        'action_url',
        'app_name',
        'member_name',
        'module_title',
        'mentor_name',
        'session_time',
        'step_title',
        'rank_name',
        'announcement_title',
    ],

    'modules' => [
        'onboarding' => ['icon' => 'clipboard-document-check', 'label' => 'Onboarding'],
        'fap' => ['icon' => 'academic-cap', 'label' => 'FAP'],
        'licensing' => ['icon' => 'document-check', 'label' => 'Licensing'],
        'training' => ['icon' => 'book-open', 'label' => 'Training'],
        'prospect' => ['icon' => 'funnel', 'label' => 'Prospects'],
        'fna' => ['icon' => 'chart-bar', 'label' => 'FNA'],
        'calendar' => ['icon' => 'calendar', 'label' => 'Calendar'],
        'booking' => ['icon' => 'calendar-days', 'label' => 'Booking'],
        'task' => ['icon' => 'check-circle', 'label' => 'Tasks'],
        'goal' => ['icon' => 'flag', 'label' => 'Goals'],
        'message' => ['icon' => 'chat-bubble-left', 'label' => 'Messages'],
        'support' => ['icon' => 'lifebuoy', 'label' => 'Support'],
        'system' => ['icon' => 'bell-alert', 'label' => 'System'],
    ],

];
