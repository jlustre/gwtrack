<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Prospect → member / client conversion bridges
    |--------------------------------------------------------------------------
    */
    'prospect' => [
        'associate_initiated_metrics' => ['invitations_sent', 'registrations'],
        'associate_completed_metrics' => ['recruits', 'registrations', 'direct_recruits'],
        'client_metrics' => ['applications', 'annual_premium'],
        'client_default_premium' => 2500,
        'create_production_on_client_conversion' => true,
        'tasks' => [
            'associate_initiated' => [
                'title' => 'Follow up on registration: {prospect}',
                'description' => 'Confirm your recruit received the invitation and completes GWTracks registration.',
                'priority' => 'high',
                'offset_days' => 2,
            ],
            'associate_completed' => [
                'title' => 'Welcome new team member: {member}',
                'description' => 'Schedule onboarding check-in and confirm sponsor alignment for your new recruit.',
                'priority' => 'medium',
                'offset_days' => 1,
            ],
            'client_conversion' => [
                'title' => 'Service new client policy: {prospect}',
                'description' => 'Confirm policy delivery, set review cadence, and request referrals.',
                'priority' => 'medium',
                'offset_days' => 3,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | FNA → goals bridges
    |--------------------------------------------------------------------------
    */
    'fna' => [
        'submitted_metrics' => ['fna_completed'],
        'approved_metrics' => ['fna_completed', 'fna_approved', 'applications'],
        'activity_keys' => [
            'submitted' => 'fnas',
            'approved' => 'fnas',
        ],
        'tasks' => [
            'approved' => [
                'title' => 'Schedule client review: {client}',
                'description' => 'FNA approved — book the client review meeting and prepare recommendations.',
                'priority' => 'high',
                'offset_days' => 2,
            ],
        ],
    ],
];
