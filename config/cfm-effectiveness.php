<?php

return [
    'scoring' => [
        'objective_weight' => (float) env('CFM_EFFECTIVENESS_OBJECTIVE_WEIGHT', 0.70),
        'feedback_weight' => (float) env('CFM_EFFECTIVENESS_FEEDBACK_WEIGHT', 0.20),
        'ao_weight' => (float) env('CFM_EFFECTIVENESS_AO_WEIGHT', 0.10),
    ],

    'objective_metrics' => [
        'retention_rate' => ['weight' => 0.15, 'label' => 'Trainee Retention'],
        'fap_completion_rate' => ['weight' => 0.15, 'label' => 'FAP Completion'],
        'licensing_completion_rate' => ['weight' => 0.15, 'label' => 'Licensing Completion'],
        'meeting_completion_rate' => ['weight' => 0.10, 'label' => 'Meeting Completion'],
        'responsiveness_score' => ['weight' => 0.10, 'label' => 'Responsiveness'],
        'coaching_activity_score' => ['weight' => 0.15, 'label' => 'Coaching Activity'],
        'goal_influence_score' => ['weight' => 0.10, 'label' => 'Goal Completion Influence'],
        'promotion_development_score' => ['weight' => 0.10, 'label' => 'Promotion Development'],
    ],

    'response_time_bands' => [
        ['max_hours' => 4, 'score' => 100, 'label' => 'Excellent'],
        ['max_hours' => 24, 'score' => 85, 'label' => 'Good'],
        ['max_hours' => 72, 'score' => 60, 'label' => 'Needs Improvement'],
        ['max_hours' => null, 'score' => 35, 'label' => 'Poor'],
    ],

    'review_triggers' => [
        '30_day' => ['label' => '30-Day Review', 'days' => 30],
        '60_day' => ['label' => '60-Day Review', 'days' => 60],
        '90_day' => ['label' => '90-Day Review', 'days' => 90],
        'fap_completion' => ['label' => 'FAP Completion'],
        'licensing_completion' => ['label' => 'Licensing Completion'],
        'promotion' => ['label' => 'Promotion Achievement'],
        'ao_requested' => ['label' => 'Agency Owner Requested'],
        'trainee_requested' => ['label' => 'Trainee CFM Evaluation'],
    ],

    'mentoring_aspect_categories' => [
        'onboarding' => ['label' => 'Onboarding & Orientation', 'description' => 'How well the CFM set expectations and welcomed the associate.'],
        'communication' => ['label' => 'Communication & Availability', 'description' => 'Responsiveness, clarity, and accessibility.'],
        'coaching' => ['label' => 'Coaching & Skill Building', 'description' => 'Practical teaching, role-play, and field readiness.'],
        'accountability' => ['label' => 'Accountability & Follow-Through', 'description' => 'Consistency of follow-up and professional standards.'],
        'systems' => ['label' => 'Systems & GWTrack Guidance', 'description' => 'Help with portal tools, processes, and workflows.'],
        'development' => ['label' => 'Personal Development & Leadership', 'description' => 'Confidence, growth mindset, and leadership modeling.'],
        'support' => ['label' => 'Support & Care', 'description' => 'Encouragement, empathy, and problem-solving support.'],
    ],

    'star_labels' => [
        1 => 'Poor',
        2 => 'Fair',
        3 => 'Good',
        4 => 'Very good',
        5 => 'Excellent',
    ],

    'ao_scorecard_categories' => [
        'leadership' => 'Leadership',
        'communication' => 'Communication',
        'mentorship_quality' => 'Mentorship Quality',
        'documentation' => 'Documentation',
        'accountability' => 'Accountability',
        'availability' => 'Availability',
        'team_development' => 'Team Development',
        'coaching_skills' => 'Coaching Skills',
        'problem_resolution' => 'Problem Resolution',
        'professionalism' => 'Professionalism',
    ],

    'risk_thresholds' => [
        'inactive_days' => 14,
        'response_hours' => 72,
        'retention_rate' => 60,
        'fap_completion_rate' => 50,
        'licensing_completion_rate' => 40,
        'trainee_satisfaction' => 3.0,
    ],

    'leaderboard_metrics' => [
        'overall_effectiveness' => 'Top Overall Effectiveness',
        'fap_completion_rate' => 'Top FAP Completion',
        'licensing_completion_rate' => 'Top Licensing Completion',
        'retention_rate' => 'Top Retention',
        'coaching_activity_score' => 'Top Coaching Score',
        'promotion_development_score' => 'Top Leadership Development',
    ],

    'report_types' => [
        'effectiveness_summary' => 'CFM Effectiveness Report',
        'quarterly_mentor' => 'Quarterly Mentor Report',
        'retention_report' => 'Retention Report',
        'licensing_report' => 'Licensing Report',
        'fap_report' => 'FAP Completion Report',
        'mentor_comparison' => 'Mentor Comparison Report',
    ],

    'recognition_award_rules' => [
        'mentor_of_month' => [
            'type' => 'leaderboard_rank',
            'metric_key' => 'overall_effectiveness',
            'rank' => 1,
        ],
        'fap_champion' => [
            'type' => 'leaderboard_rank',
            'metric_key' => 'fap_completion_rate',
            'rank' => 1,
        ],
        'licensing_champion' => [
            'type' => 'leaderboard_rank',
            'metric_key' => 'licensing_completion_rate',
            'rank' => 1,
        ],
        'retention_champion' => [
            'type' => 'leaderboard_rank',
            'metric_key' => 'retention_rate',
            'rank' => 1,
        ],
        'leadership_builder' => [
            'type' => 'leaderboard_rank',
            'metric_key' => 'promotion_development_score',
            'rank' => 1,
        ],
        'top_coach' => [
            'type' => 'leaderboard_rank',
            'metric_key' => 'coaching_activity_score',
            'rank' => 1,
        ],
        'fast_track_mentor' => [
            'type' => 'leaderboard_rank',
            'metric_key' => 'overall_effectiveness',
            'rank' => 2,
        ],
        'rising_mentor' => [
            'type' => 'most_improved',
            'metric_key' => 'overall_effectiveness',
            'min_improvement' => 5,
        ],
    ],
];
