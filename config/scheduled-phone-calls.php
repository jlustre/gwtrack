<?php

return [
    'person_types' => [
        'prospect' => 'Prospect',
        'client' => 'Client',
        'team_member' => 'Team Member',
        'external' => 'Other Person',
    ],

    'results' => [
        'not_contacted' => 'Not Contacted',
        'callback' => 'Contacted But Need to Call back',
        'completed' => 'Completed',
    ],

    'crm_outcomes' => [
        'not_contacted' => 'Not contacted',
        'callback' => 'Need to call back',
        'completed' => 'Completed',
    ],

    'sync' => [
        'calendar' => true,
        'prep_task' => true,
        'prep_task_title' => 'Prepare for phone call: :contact',
        'prep_task_offset_days' => 0,
        'default_duration_minutes' => 30,
    ],
];
