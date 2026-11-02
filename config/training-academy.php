<?php

return [
    'brand' => [
        'name' => 'GWTracks Academy',
        'tagline' => 'Learn, certify, lead, and grow your financial services career.',
    ],

    'course_types' => [
        'video' => 'Video Course',
        'document' => 'Document-Based Course',
        'interactive' => 'Interactive Course',
        'webinar' => 'Webinar Recording',
        'live' => 'Live Training',
        'certification' => 'Certification Program',
        'coaching' => 'Coaching Program',
    ],

    'difficulties' => [
        'beginner' => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced' => 'Advanced',
        'expert' => 'Expert',
    ],

    'badge_levels' => [
        'bronze' => 'Bronze',
        'silver' => 'Silver',
        'gold' => 'Gold',
        'platinum' => 'Platinum',
        'diamond' => 'Diamond',
    ],

    'resource_library_categories' => [
        'prospecting' => 'Prospecting',
        'recruiting' => 'Recruiting',
        'presentation' => 'Presentation Skills',
        'fna' => 'FNA',
        'insurance_products' => 'Insurance Products',
        'leadership' => 'Leadership',
        'goal_setting' => 'Goal Setting',
        'time_management' => 'Time Management',
        'objection_handling' => 'Objection Handling',
        'compliance' => 'Compliance',
        'marketing' => 'Marketing',
        'social_media' => 'Social Media',
        'personal_development' => 'Personal Development',
        'spiritual_development' => 'Spiritual Development',
    ],

    'default_paths' => [
        [
            'code' => 'new-associate',
            'name' => 'New Associate Path',
            'audience' => 'associate',
            'description' => 'Welcome, compliance, FNA, prospecting, presentation, follow-up, and field apprenticeship readiness.',
        ],
        [
            'code' => 'licensing',
            'name' => 'Licensing Path',
            'audience' => 'associate',
            'description' => 'Provincial licensing, LLQP, state licensing, continuing education, and exam preparation.',
        ],
        [
            'code' => 'cfm-certification',
            'name' => 'CFM Certification Path',
            'audience' => 'mentor',
            'description' => 'Coaching, leadership, FAP management, mentorship, accountability, and evaluation.',
        ],
        [
            'code' => 'agency-owner',
            'name' => 'Agency Owner Path',
            'audience' => 'leader',
            'description' => 'Leadership, recruiting, retention, team building, compliance, and culture development.',
        ],
    ],

    'integrations' => [
        'checklist_types' => [
            'fap' => 'fap',
            'cfm_training' => 'cfm-training',
            'licensing' => 'licensing',
        ],
        'goal_metrics' => [
            'training_completion' => 'training_completion',
            'cfm_training_completion' => 'cfm_training_completion',
            'fap_completion' => 'fap_completion',
        ],
    ],

    'assessments' => [
        'max_attempts' => 3,
        'allow_retakes_after_pass' => false,
        'require_course_completion' => true,
    ],

    'certifications' => [
        'certificate_prefix' => 'GW',
        'validity_years' => null,
    ],

    'assignments' => [
        'default_due_days' => 30,
    ],

    'coaching' => [
        'review_types' => [
            'coaching' => 'Coaching Session',
            'field_observation' => 'Field Observation',
            'fap_signoff' => 'FAP Sign-Off',
        ],
        'fap_signoff_min_percent' => 90,
        'session_types' => [
            'live' => 'Live Coaching',
            'webinar' => 'Webinar',
            'field' => 'Field Training',
        ],
    ],

    'calendar' => [
        'event_type_slug' => 'training-session',
        'webinar_type_slug' => 'recorded-webinar-review',
        'field_type_slug' => 'field-observation',
        'visibility' => 'public_organization',
    ],

    'gamification' => [
        'points' => [
            'lesson_completed' => 1,
            'course_completed' => 10,
            'assessment_passed' => 15,
            'certification_issued' => 25,
            'session_attended' => 5,
            'path_completed' => 20,
        ],
        'module_badges' => [
            'prospecting-certified' => 'prospecting-fundamentals',
            'presentation-expert' => 'presentation-mastery',
        ],
        'streak_badges' => [
            'learning-streak-3' => 3,
            'learning-streak-7' => 7,
            'learning-streak-14' => 14,
        ],
        'leaderboard_limit' => 10,
    ],

    'recommendations' => [
        'inactive_days' => 14,
        'licensing_behind_percent' => 50,
        'role_paths' => [
            'new-recruit' => 'new-associate',
            'associate' => 'new-associate',
            'member' => 'new-associate',
            'certified-field-mentor' => 'cfm-certification',
            'trainer' => 'cfm-certification',
            'team-leader' => 'agency-owner',
            'agency-owner' => 'agency-owner',
        ],
        'reasons' => [
            'overdue_assignment' => ['label' => 'Overdue assignment', 'action' => 'Resume course', 'priority' => 100],
            'continue_course' => ['label' => 'Continue learning', 'action' => 'Resume course', 'priority' => 95],
            'path_next_course' => ['label' => 'Learning path', 'action' => 'Open course', 'priority' => 90],
            'enroll_path' => ['label' => 'Recommended path', 'action' => 'View path', 'priority' => 88],
            'assessment_ready' => ['label' => 'Assessment ready', 'action' => 'Take assessment', 'priority' => 85],
            'fap_not_started' => ['label' => 'FAP', 'action' => 'Open FAP checklist', 'priority' => 80],
            'fap_in_progress' => ['label' => 'FAP progress', 'action' => 'Open FAP checklist', 'priority' => 78],
            'licensing_behind' => ['label' => 'Licensing', 'action' => 'Open licensing tracker', 'priority' => 75],
            'cfm_training' => ['label' => 'CFM training', 'action' => 'Open CFM training', 'priority' => 72],
            'inactive_learning' => ['label' => 'Get back on track', 'action' => 'Resume learning', 'priority' => 65],
            'featured_course' => ['label' => 'Featured course', 'action' => 'Explore course', 'priority' => 45],
            'on_track' => ['label' => 'On track', 'action' => 'View learning plan', 'priority' => 20],
        ],
    ],
];
