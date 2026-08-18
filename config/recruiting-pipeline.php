<?php

return [
    'funnel_key' => 'recruiting',

    'candidate_funnel_types' => ['recruiting', 'both'],

    'journey_stages' => [
        'registered' => 'Registered',
        'onboarding' => 'Onboarding',
        'licensing' => 'Licensing',
        'fap' => 'Field Apprenticeship',
        'licensed_producer' => 'Licensed Producer',
    ],

    'journey_thresholds' => [
        'onboarding' => 100,
        'licensing' => 100,
        'fap' => 100,
    ],

    'candidate_list_limit' => 25,

    'active_recruit_limit' => 25,

    'invitation_list_limit' => 10,
];
