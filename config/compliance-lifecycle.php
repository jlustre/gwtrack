<?php

return [
    'types' => [
        'state_license' => [
            'label' => 'State / Provincial License',
            'description' => 'Life insurance license renewals by jurisdiction.',
            'renewal_window_days' => 90,
            'tracks_jurisdiction' => true,
            'tracks_identifier' => true,
            'tracks_credits' => false,
            'tracks_carrier' => false,
        ],
        'eo_insurance' => [
            'label' => 'E&O Insurance',
            'description' => 'Errors & omissions coverage renewal.',
            'renewal_window_days' => 60,
            'tracks_jurisdiction' => false,
            'tracks_identifier' => true,
            'tracks_credits' => false,
            'tracks_carrier' => true,
        ],
        'aml_training' => [
            'label' => 'AML Training',
            'description' => 'Anti-money laundering training completion and refreshers.',
            'renewal_window_days' => 30,
            'tracks_jurisdiction' => false,
            'tracks_identifier' => false,
            'tracks_credits' => false,
            'tracks_carrier' => false,
        ],
        'carrier_appointment' => [
            'label' => 'Carrier Appointment',
            'description' => 'Carrier contracting and appointment renewals.',
            'renewal_window_days' => 45,
            'tracks_jurisdiction' => true,
            'tracks_identifier' => true,
            'tracks_credits' => false,
            'tracks_carrier' => true,
        ],
        'ce_credits' => [
            'label' => 'Continuing Education',
            'description' => 'CE credit requirements by reporting period.',
            'renewal_window_days' => 90,
            'tracks_jurisdiction' => true,
            'tracks_identifier' => false,
            'tracks_credits' => true,
            'tracks_carrier' => false,
        ],
        'background_check' => [
            'label' => 'Background / Suitability',
            'description' => 'Background checks and suitability filings.',
            'renewal_window_days' => 30,
            'tracks_jurisdiction' => false,
            'tracks_identifier' => false,
            'tracks_credits' => false,
            'tracks_carrier' => false,
        ],
    ],

    'statuses' => [
        'not_started' => 'Not started',
        'active' => 'Active',
        'pending_renewal' => 'Renewal due',
        'pending_verification' => 'Pending verification',
        'expired' => 'Expired',
    ],

    'reminder_days' => [90, 60, 30, 14, 7],

    'notification_trigger' => 'compliance_renewal_due',
];
