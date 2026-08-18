<?php

return [
    'category' => [
        'name' => 'Prospects',
        'slug' => 'prospects',
        'color' => '#16A34A',
        'icon' => 'target',
        'sort_order' => 40,
    ],

    'event_types' => [
        'sales-appointment' => [
            'name' => 'Sales Appointment',
            'color' => '#14532D',
            'sort_order' => 410,
        ],
        'fna-appointment' => [
            'name' => 'FNA Appointment',
            'color' => '#92400E',
            'sort_order' => 420,
        ],
        'policy-review' => [
            'name' => 'Policy Review',
            'color' => '#7C2D12',
            'sort_order' => 430,
        ],
        'recruiting-presentation' => [
            'name' => 'Recruiting Presentation',
            'color' => '#065F46',
            'sort_order' => 440,
        ],
        'recruiting-follow-up' => [
            'name' => 'Recruiting Follow-Up',
            'color' => '#0F766E',
            'sort_order' => 450,
        ],
        'orientation-training' => [
            'name' => 'Orientation / Training',
            'color' => '#1D4ED8',
            'sort_order' => 460,
        ],
        'crm-follow-up' => [
            'name' => 'CRM Follow-Up',
            'color' => '#15803D',
            'sort_order' => 470,
        ],
    ],

    'appointment_type_map' => [
        'financial-needs-analysis' => 'fna-appointment',
        'product-presentation' => [
            'insurance' => 'sales-appointment',
            'recruiting' => 'recruiting-presentation',
            'default' => 'sales-appointment',
        ],
        'career-overview' => 'recruiting-presentation',
        'application-review' => [
            'insurance' => 'sales-appointment',
            'recruiting' => 'orientation-training',
            'default' => 'sales-appointment',
        ],
        'follow-up-meeting' => [
            'recruiting' => 'recruiting-follow-up',
            'default' => 'crm-follow-up',
        ],
        'mentor-assisted-call' => 'sales-appointment',
        'discovery-call' => 'sales-appointment',
        'policy-review' => 'policy-review',
    ],

    'default_event_type' => [
        'insurance' => 'sales-appointment',
        'recruiting' => 'recruiting-presentation',
        'both' => 'sales-appointment',
        'default' => 'crm-follow-up',
    ],
];
