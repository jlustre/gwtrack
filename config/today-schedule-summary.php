<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Today schedule summary buckets
    |--------------------------------------------------------------------------
    | Classify calendar event types and CRM appointment types into summary rows.
    | First matching bucket wins when an item could match multiple definitions.
    */
    'buckets' => [
        'phone_calls' => [
            'label' => 'Phone Calls',
            'calendar_type_slugs' => [
                'follow-up-block',
                'crm-follow-up',
                'recruiting-follow-up',
            ],
            'appointment_type_slugs' => [
                'discovery-call',
                'mentor-assisted-call',
            ],
        ],
        'presentations' => [
            'label' => 'Presentations',
            'calendar_type_slugs' => [
                'recruiting-presentation',
            ],
            'appointment_type_slugs' => [
                'product-presentation',
                'career-overview',
            ],
        ],
        'meetings' => [
            'label' => 'Meetings',
            'calendar_type_slugs' => [
                'team-meeting',
                'sales-appointment',
                'fna-appointment',
                'policy-review',
                'prospect-appointment',
                'training-session',
                'fap-mentor-session',
                'field-observation',
                'cfm-training',
                'orientation-training',
            ],
            'appointment_type_slugs' => [
                'financial-needs-analysis',
                'follow-up-meeting',
                'application-review',
            ],
        ],
    ],

    'fallback_bucket' => 'meetings',

    'other_bucket' => [
        'label' => 'Other Events',
    ],
];
