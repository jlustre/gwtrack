<?php

return [
    'statuses' => [
        'not_started' => 'Not started',
        'in_progress' => 'In progress',
        'submitted' => 'Submitted',
        'pending_confirmation' => 'Pending confirmation',
        'ready_for_review' => 'Ready for review',
        'completed' => 'Completed',
        'rejected' => 'Needs revision',
    ],

    'member_actionable' => [
        'not_started',
        'in_progress',
        'rejected',
    ],

    'review_queue' => [
        'submitted',
        'pending_confirmation',
        'ready_for_review',
    ],

    'completed_statuses' => [
        'completed',
    ],

    'categories' => [
        'licensing' => 'Licensing & Compliance',
        'training' => 'Training & Certification',
        'production' => 'Production',
        'recruiting' => 'Recruiting & Team Building',
        'leadership' => 'Leadership Development',
        'field_activity' => 'Field Activity',
        'personal_development' => 'Personal Development',
        'general' => 'General Requirements',
    ],

    'category_order' => [
        'licensing',
        'training',
        'production',
        'recruiting',
        'field_activity',
        'leadership',
        'personal_development',
        'general',
    ],
];
