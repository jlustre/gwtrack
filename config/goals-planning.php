<?php

return [

    'goal_types' => [
        'outcome' => 'Outcome Goal',
        'activity' => 'Activity Goal',
    ],

    'planning_types' => [
        'income' => [
            'label' => 'Annual Income Goal',
            'description' => 'Reverse-engineer production and daily activities from your income target.',
            'funnel' => 'income',
            'root_stage' => 'annual_income',
            'category_slug' => 'income',
            'measurement_type' => 'currency',
        ],
        'production' => [
            'label' => 'Production Goal',
            'description' => 'Plan applications, FNAs, presentations, and prospecting from premium targets.',
            'funnel' => 'production',
            'root_stage' => 'annual_production',
            'category_slug' => 'production',
            'measurement_type' => 'currency',
        ],
        'recruiting' => [
            'label' => 'Recruiting Goal',
            'description' => 'Build a recruiting funnel from your recruit target.',
            'funnel' => 'recruiting',
            'root_stage' => 'annual_recruits',
            'category_slug' => 'recruiting',
            'measurement_type' => 'number',
        ],
        'rank' => [
            'label' => 'Rank Advancement Goal',
            'description' => 'Roadmap production, recruiting, training, and leadership requirements.',
            'funnel' => 'rank',
            'root_stage' => 'target_rank',
            'category_slug' => 'rank_advancement',
            'measurement_type' => 'completion',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Funnel stages — ordered top (outcome) to bottom (activity)
    |--------------------------------------------------------------------------
    */
    'funnels' => [
        'income' => [
            ['key' => 'annual_income', 'label' => 'Annual Income', 'goal_type' => 'outcome', 'metric_key' => 'annual_income', 'measurement' => 'currency'],
            ['key' => 'annual_production', 'label' => 'Annual Production', 'goal_type' => 'outcome', 'metric_key' => 'annual_premium', 'measurement' => 'currency'],
            ['key' => 'monthly_production', 'label' => 'Monthly Production', 'goal_type' => 'outcome', 'metric_key' => 'monthly_premium', 'measurement' => 'currency'],
            ['key' => 'weekly_production', 'label' => 'Weekly Production', 'goal_type' => 'outcome', 'metric_key' => 'monthly_premium', 'measurement' => 'currency'],
            ['key' => 'applications', 'label' => 'Applications Submitted', 'goal_type' => 'activity', 'metric_key' => 'applications', 'measurement' => 'number'],
            ['key' => 'fnas', 'label' => 'FNAs Completed', 'goal_type' => 'activity', 'metric_key' => 'fna_completed', 'measurement' => 'number'],
            ['key' => 'presentations', 'label' => 'Presentations', 'goal_type' => 'activity', 'metric_key' => 'presentations', 'measurement' => 'number'],
            ['key' => 'appointments', 'label' => 'Appointments Held', 'goal_type' => 'activity', 'metric_key' => 'appointments', 'measurement' => 'number'],
            ['key' => 'invitations', 'label' => 'Invitations Sent', 'goal_type' => 'activity', 'metric_key' => 'invitations_sent', 'measurement' => 'number'],
            ['key' => 'prospect_contacts', 'label' => 'Prospect Conversations', 'goal_type' => 'activity', 'metric_key' => 'contacts', 'measurement' => 'number'],
            ['key' => 'daily_contacts', 'label' => 'Daily Prospecting Contacts', 'goal_type' => 'activity', 'metric_key' => 'contacts', 'measurement' => 'number'],
        ],
        'production' => [
            ['key' => 'annual_production', 'label' => 'Annual Production', 'goal_type' => 'outcome', 'metric_key' => 'annual_premium', 'measurement' => 'currency'],
            ['key' => 'monthly_production', 'label' => 'Monthly Production', 'goal_type' => 'outcome', 'metric_key' => 'monthly_premium', 'measurement' => 'currency'],
            ['key' => 'applications', 'label' => 'Applications', 'goal_type' => 'activity', 'metric_key' => 'applications', 'measurement' => 'number'],
            ['key' => 'fnas', 'label' => 'FNAs', 'goal_type' => 'activity', 'metric_key' => 'fna_completed', 'measurement' => 'number'],
            ['key' => 'presentations', 'label' => 'Presentations', 'goal_type' => 'activity', 'metric_key' => 'presentations', 'measurement' => 'number'],
            ['key' => 'appointments', 'label' => 'Appointments', 'goal_type' => 'activity', 'metric_key' => 'appointments', 'measurement' => 'number'],
            ['key' => 'invitations', 'label' => 'Invitations', 'goal_type' => 'activity', 'metric_key' => 'invitations_sent', 'measurement' => 'number'],
            ['key' => 'prospect_contacts', 'label' => 'Prospect Contacts', 'goal_type' => 'activity', 'metric_key' => 'contacts', 'measurement' => 'number'],
        ],
        'recruiting' => [
            ['key' => 'annual_recruits', 'label' => 'Annual Recruits', 'goal_type' => 'outcome', 'metric_key' => 'recruits', 'measurement' => 'number'],
            ['key' => 'monthly_recruits', 'label' => 'Monthly Recruits', 'goal_type' => 'outcome', 'metric_key' => 'recruits', 'measurement' => 'number'],
            ['key' => 'registrations', 'label' => 'Registrations', 'goal_type' => 'activity', 'metric_key' => 'registrations', 'measurement' => 'number'],
            ['key' => 'recruiting_presentations', 'label' => 'Opportunity Presentations', 'goal_type' => 'activity', 'metric_key' => 'presentations', 'measurement' => 'number'],
            ['key' => 'recruiting_appointments', 'label' => 'Recruiting Appointments', 'goal_type' => 'activity', 'metric_key' => 'appointments', 'measurement' => 'number'],
            ['key' => 'recruiting_invitations', 'label' => 'Recruiting Invitations', 'goal_type' => 'activity', 'metric_key' => 'invitations_sent', 'measurement' => 'number'],
            ['key' => 'recruiting_contacts', 'label' => 'Prospect Contacts', 'goal_type' => 'activity', 'metric_key' => 'contacts', 'measurement' => 'number'],
        ],
        'rank' => [
            ['key' => 'target_rank', 'label' => 'Target Rank', 'goal_type' => 'outcome', 'metric_key' => 'rank_requirements', 'measurement' => 'completion'],
            ['key' => 'rank_production', 'label' => 'Production Required', 'goal_type' => 'outcome', 'metric_key' => 'annual_premium', 'measurement' => 'currency'],
            ['key' => 'rank_recruits', 'label' => 'Recruits Required', 'goal_type' => 'outcome', 'metric_key' => 'team_recruits', 'measurement' => 'number'],
            ['key' => 'rank_licensing', 'label' => 'Licensing Progress', 'goal_type' => 'activity', 'metric_key' => 'licensing_completion', 'measurement' => 'percentage'],
            ['key' => 'rank_fap', 'label' => 'FAP Completion', 'goal_type' => 'activity', 'metric_key' => 'fap_completion', 'measurement' => 'percentage'],
            ['key' => 'rank_training', 'label' => 'Training Completion', 'goal_type' => 'activity', 'metric_key' => 'training_completion', 'measurement' => 'percentage'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default conversion rates (from_stage => to_stage => rate)
    | Rate = probability of advancing to next stage per period
    |--------------------------------------------------------------------------
    */
    'default_conversion_rates' => [
        'income' => [
            'annual_income' => ['annual_production' => 5.0],
            'annual_production' => ['monthly_production' => 12.0],
            'monthly_production' => ['weekly_production' => 4.33],
            'weekly_production' => ['applications' => 0.15],
            'applications' => ['fnas' => 0.85],
            'fnas' => ['presentations' => 0.75],
            'presentations' => ['appointments' => 0.60],
            'appointments' => ['invitations' => 0.50],
            'invitations' => ['prospect_contacts' => 0.40],
            'prospect_contacts' => ['daily_contacts' => 0.20],
        ],
        'production' => [
            'annual_production' => ['monthly_production' => 12.0],
            'monthly_production' => ['applications' => 0.12],
            'applications' => ['fnas' => 0.85],
            'fnas' => ['presentations' => 0.75],
            'presentations' => ['appointments' => 0.60],
            'appointments' => ['invitations' => 0.50],
            'invitations' => ['prospect_contacts' => 0.40],
        ],
        'recruiting' => [
            'annual_recruits' => ['monthly_recruits' => 12.0],
            'monthly_recruits' => ['registrations' => 0.50],
            'registrations' => ['recruiting_presentations' => 0.50],
            'recruiting_presentations' => ['recruiting_appointments' => 0.40],
            'recruiting_appointments' => ['recruiting_invitations' => 0.40],
            'recruiting_invitations' => ['recruiting_contacts' => 0.50],
        ],
        'rank' => [
            'target_rank' => ['rank_production' => 1.0],
            'rank_production' => ['rank_recruits' => 1.0],
            'rank_recruits' => ['rank_licensing' => 1.0],
            'rank_licensing' => ['rank_fap' => 1.0],
            'rank_fap' => ['rank_training' => 1.0],
        ],
    ],

    'planning_constants' => [
        'income_commission_rate' => 0.20,
        'avg_annual_premium_per_application' => 2500,
        'working_days_per_month' => 22,
        'working_weeks_per_year' => 48,
        'weeks_per_month' => 4.33,
    ],

    /*
    |--------------------------------------------------------------------------
    | User-editable planning settings (labels for settings UI)
    |--------------------------------------------------------------------------
    */
    'settings_fields' => [
        'income_commission_rate' => [
            'label' => 'Income commission rate',
            'description' => 'What percentage of your annual production is paid to you as income. Used to reverse-engineer how much production you need from an income target.',
            'examples' => [
                'Earn $100,000 at 20% commission → you need about $500,000 in annual production.',
                'Earn $80,000 at 25% commission → you need about $320,000 in annual production.',
            ],
            'type' => 'percent',
            'min' => 1,
            'max' => 100,
            'step' => 0.5,
        ],
        'avg_annual_premium_per_application' => [
            'label' => 'Average annual premium per application',
            'description' => 'Typical annual premium per submitted application. Used to estimate how many applications you need for a production target.',
            'examples' => [
                '$2,500 average premium and $60,000 monthly production → about 24 applications per month.',
                'If your typical case is larger (for example $4,000), enter that so fewer applications are projected.',
            ],
            'type' => 'currency',
            'min' => 100,
            'max' => 1000000,
            'step' => 100,
        ],
        'working_days_per_month' => [
            'label' => 'Working days per month',
            'description' => 'Business days you plan to work each month when calculating daily activity targets.',
            'examples' => [
                '22 days ≈ full-time weekday schedule with light time off.',
                '16 days ≈ part-time or travel-heavy month; daily targets rise because the same monthly work is packed into fewer days.',
            ],
            'type' => 'integer',
            'min' => 1,
            'max' => 31,
            'step' => 1,
        ],
        'working_weeks_per_year' => [
            'label' => 'Working weeks per year',
            'description' => 'Weeks per year you actively work toward goals (excluding vacations or inactive periods).',
            'examples' => [
                '48 weeks ≈ 4 weeks of vacation/holiday across the year.',
                '50 weeks ≈ mostly full-year activity with very little downtime.',
            ],
            'type' => 'integer',
            'min' => 1,
            'max' => 52,
            'step' => 1,
        ],
        'weeks_per_month' => [
            'label' => 'Weeks per month',
            'description' => 'Average weeks in a month used to convert monthly targets into weekly targets.',
            'examples' => [
                '4.33 is the common average (52 weeks ÷ 12 months).',
                'Use 4.0 if you plan in clean 4-week blocks for coaching simplicity.',
            ],
            'type' => 'decimal',
            'min' => 1,
            'max' => 5,
            'step' => 0.01,
        ],
    ],

    'editable_conversion_rates' => [
        'income' => [
            [
                'from' => 'applications',
                'to' => 'fnas',
                'label' => 'Applications → FNA completed',
                'description' => 'Percent of submitted applications that also get a completed Financial Needs Analysis.',
                'examples' => [
                    '10 applications and 8 completed FNAs → enter 80%.',
                    'A lower rate means the planner will ask for more applications to hit the same production.',
                ],
            ],
            [
                'from' => 'fnas',
                'to' => 'presentations',
                'label' => 'FNA → Presentation delivered',
                'description' => 'Percent of completed FNAs that move into a formal product or opportunity presentation.',
                'examples' => [
                    '8 FNAs and 6 presentations → enter 75%.',
                    'If many FNAs stall before presentation, lower this rate.',
                ],
            ],
            [
                'from' => 'presentations',
                'to' => 'appointments',
                'label' => 'Presentation → Appointment held',
                'description' => 'Percent of presentations that come from held appointments in your funnel math.',
                'examples' => [
                    'If roughly 3 of 5 appointments result in a presentation, enter about 60%.',
                ],
            ],
            [
                'from' => 'appointments',
                'to' => 'invitations',
                'label' => 'Appointment → Invitation sent',
                'description' => 'Percent of invitations that convert into a held appointment.',
                'examples' => [
                    '20 invitations and 10 appointments → enter 50%.',
                ],
            ],
            [
                'from' => 'invitations',
                'to' => 'prospect_contacts',
                'label' => 'Invitation → Prospect contact',
                'description' => 'Percent of prospect conversations that become invitations.',
                'examples' => [
                    '50 contacts and 20 invitations → enter 40%.',
                    'Warm-market contacts often convert higher than cold outreach.',
                ],
            ],
        ],
        'production' => [
            [
                'from' => 'applications',
                'to' => 'fnas',
                'label' => 'Applications → FNA completed',
                'description' => 'Percent of submitted applications that also get a completed Financial Needs Analysis.',
                'examples' => [
                    '10 applications and 8 completed FNAs → enter 80%.',
                ],
            ],
            [
                'from' => 'fnas',
                'to' => 'presentations',
                'label' => 'FNA → Presentation delivered',
                'description' => 'Percent of completed FNAs that move into a formal presentation.',
                'examples' => [
                    '8 FNAs and 6 presentations → enter 75%.',
                ],
            ],
            [
                'from' => 'presentations',
                'to' => 'appointments',
                'label' => 'Presentation → Appointment held',
                'description' => 'Percent of presentations that come from held appointments.',
                'examples' => [
                    'If 3 of 5 appointments become presentations, enter about 60%.',
                ],
            ],
            [
                'from' => 'appointments',
                'to' => 'invitations',
                'label' => 'Appointment → Invitation sent',
                'description' => 'Percent of invitations that convert into a held appointment.',
                'examples' => [
                    '20 invitations and 10 appointments → enter 50%.',
                ],
            ],
            [
                'from' => 'invitations',
                'to' => 'prospect_contacts',
                'label' => 'Invitation → Prospect contact',
                'description' => 'Percent of prospect conversations that become invitations.',
                'examples' => [
                    '50 contacts and 20 invitations → enter 40%.',
                ],
            ],
        ],
        'recruiting' => [
            [
                'from' => 'monthly_recruits',
                'to' => 'registrations',
                'label' => 'Monthly recruit target → Registration',
                'description' => 'Percent of registrations that become joined recruits for the month.',
                'examples' => [
                    'If half of registrations join, enter 50%.',
                    'Target 2 recruits/month at 50% → plan for about 4 registrations.',
                ],
            ],
            [
                'from' => 'registrations',
                'to' => 'recruiting_presentations',
                'label' => 'Registration → Opportunity presentation',
                'description' => 'Percent of opportunity presentations that lead to a registration.',
                'examples' => [
                    '10 presentations and 5 registrations → enter 50%.',
                ],
            ],
            [
                'from' => 'recruiting_presentations',
                'to' => 'recruiting_appointments',
                'label' => 'Presentation → Recruiting appointment',
                'description' => 'Percent of recruiting appointments that result in an opportunity presentation.',
                'examples' => [
                    'If 2 of 5 recruiting appointments become presentations, enter 40%.',
                ],
            ],
            [
                'from' => 'recruiting_appointments',
                'to' => 'recruiting_invitations',
                'label' => 'Appointment → Recruiting invitation',
                'description' => 'Percent of recruiting invitations that become held appointments.',
                'examples' => [
                    '25 invitations and 10 appointments → enter 40%.',
                ],
            ],
            [
                'from' => 'recruiting_invitations',
                'to' => 'recruiting_contacts',
                'label' => 'Invitation → Prospect contact',
                'description' => 'Percent of recruiting prospect contacts that receive an invitation.',
                'examples' => [
                    '40 contacts and 20 invitations → enter 50%.',
                ],
            ],
        ],
    ],

    'activity_scorecard' => [
        'new_prospects' => [
            'label' => 'Leads / Prospects',
            'metric_key' => 'new_prospects',
            'description' => 'New people added to your prospect list during the selected period. Counted automatically from Prospects.',
            'examples' => [
                'Adding Maria from a referral increases this by 1.',
                'Importing or creating 5 new warm-market names in one week = 5.',
            ],
        ],
        'monthly_premium' => [
            'label' => 'Production',
            'metric_key' => 'monthly_premium',
            'description' => 'Premium production recorded in the system for the selected period. Not entered manually on this scorecard.',
            'examples' => [
                'A submitted case with $3,000 annual premium adds $3,000 here.',
                'Use Production entries / team production screens as the source of truth.',
            ],
        ],
        'recruits' => [
            'label' => 'Recruits',
            'metric_key' => 'recruits',
            'description' => 'People who converted into associates/recruits in the selected period.',
            'examples' => [
                'A prospect who completes enrollment and becomes an associate counts as 1 recruit.',
                'Someone still only in the recruiting conversation stage does not count yet.',
            ],
        ],
        'contacts' => [
            'label' => 'Initial phone calls',
            'metric_key' => 'contacts',
            'description' => 'First outreach conversations to start a relationship. Includes CRM contact activity plus your daily +/− tally.',
            'examples' => [
                'Calling a new lead for the first time.',
                'A first Facebook/Messenger conversation that opens the relationship.',
            ],
        ],
        'followups_completed' => [
            'label' => 'Follow-ups',
            'metric_key' => 'followups_completed',
            'description' => 'Completed follow-up touches after the first contact — calls, texts, or check-ins that move the relationship forward.',
            'examples' => [
                'Calling back after “text me next week.”',
                'Sending a promised brochure and confirming they received it.',
            ],
        ],
        'presentation_invitations' => [
            'label' => 'Invitations',
            'metric_key' => 'presentation_invitations',
            'description' => 'Invitations to attend a sales or opportunity presentation (in person, Zoom, or event).',
            'examples' => [
                'Inviting a couple to Thursday’s business overview.',
                'Sending a calendar invite for a product presentation.',
            ],
        ],
        'presentations' => [
            'label' => 'Presentations',
            'metric_key' => 'presentations',
            'description' => 'Presentations you delivered — sales, product, or recruiting opportunity presentations.',
            'examples' => [
                'Completing a full needs-based product presentation.',
                'Delivering a recruiting opportunity overview to a candidate.',
            ],
        ],
        'fna_started' => [
            'label' => 'FNA started',
            'metric_key' => 'fna_started',
            'description' => 'Financial Needs Analyses you started with a client/prospect. Manual tally for licensed associates.',
            'examples' => [
                'Opening an FNA worksheet during a fact-finding appointment.',
                'Beginning the digital FNA form after the discovery meeting.',
            ],
        ],
    ],

    'wizard_fields' => [
        'name' => [
            'description' => 'A clear, measurable goal title people can understand at a glance.',
            'examples' => [
                'Recruit 4 associates this quarter',
                'Submit $25,000 monthly premium by September 30',
            ],
        ],
        'description' => [
            'description' => 'Describe what success looks like, why it matters, and any constraints.',
            'examples' => [
                'Focus on warm market first; celebrate when 4 associates complete enrollment.',
                'Prioritize mortgage protection cases averaging $2,500 premium.',
            ],
        ],
        'hierarchy_level' => [
            'description' => 'How this goal fits in your planning horizon. Shorter goals should support longer ones.',
            'examples' => [
                'Annual: $120,000 income for the year',
                'Weekly: 25 prospecting contacts supporting that annual plan',
            ],
        ],
        'parent_goal' => [
            'description' => 'Optionally connect this goal under a larger parent so progress can roll up.',
            'examples' => [
                'Weekly contact goal under a monthly production goal',
                'Monthly recruit goal under an annual leadership goal',
            ],
        ],
        'target_value' => [
            'description' => 'The number you are aiming to hit by the deadline.',
            'examples' => [
                '4 for recruits',
                '25000 for monthly premium dollars',
                '100 for a completion percentage',
            ],
        ],
        'metric_key' => [
            'description' => 'Link an automated KPI so progress updates from system activity when possible. Choose Manual tracking if you will update progress yourself.',
            'examples' => [
                'Recruits → counts associate conversions',
                'Applications → counts submitted applications',
                'Manual tracking → you enter progress in the goal card',
            ],
        ],
        'measurement_type' => [
            'description' => 'How progress toward the target should be measured and displayed.',
            'examples' => [
                'Number: 12 appointments',
                'Currency: $50,000 premium',
                'Percentage: 100% FAP completion',
                'Completion: done / not done milestones',
            ],
        ],
        'starts_at' => [
            'description' => 'When you begin working this goal. Used for pace and timeline views.',
            'examples' => [
                'Start of the month for a monthly production sprint',
                'Today when creating a new weekly activity goal',
            ],
        ],
        'deadline_at' => [
            'description' => 'The date by which the target should be reached. Required for on-track coaching and reminders.',
            'examples' => [
                'End of quarter for a recruiting push',
                'Last working day of the month for premium goals',
            ],
        ],
        'milestones' => [
            'description' => 'Break the goal into checkpoints so you can celebrate progress and catch drift early.',
            'examples' => [
                'Week 2: 2 recruits enrolled',
                'Month 1: $15,000 premium submitted',
            ],
        ],
        'accountability_partner' => [
            'description' => 'Someone who can coach or check in on this goal with you.',
            'examples' => [
                'Your mentor or CFM for recruiting goals',
                'Your sponsor for weekly activity accountability',
            ],
        ],
        'notifications' => [
            'description' => 'Choose how you want progress updates and reminders delivered.',
            'examples' => [
                'Weekly check-in reminder every Monday morning',
                'In-app alerts when the goal falls off track',
            ],
        ],
        'plan_target_value' => [
            'description' => 'The outcome you want the Performance Planner to reverse-engineer into daily activities.',
            'examples' => [
                'Income plan: 100000 for $100,000 annual income',
                'Recruiting plan: 24 for 24 recruits in the year',
            ],
        ],
        'plan_name' => [
            'description' => 'A friendly name for this Success Blueprint so you can find it later.',
            'examples' => [
                '2026 Income Blueprint',
                'Q3 Recruiting Push',
            ],
        ],
        'plan_deadline' => [
            'description' => 'When the blueprint outcomes should be achieved.',
            'examples' => [
                'December 31 for an annual income plan',
                'September 30 for a quarterly recruiting plan',
            ],
        ],
        'target_rank' => [
            'description' => 'The rank you are planning to achieve. Requirements are converted into production, recruiting, and training activity.',
            'examples' => [
                'SA with $10,000 LTI for Senior Associate',
                'SVP when planning Field Leader / Builder requirements',
            ],
        ],
    ],

    'fap_activities' => [
        'observation_sessions' => 'Observation Sessions',
        'practice_presentations' => 'Practice Presentations',
        'mentor_meetings' => 'Mentor Meetings',
        'prospecting_activities' => 'Prospecting Activities',
        'training_completion' => 'Training Completion',
        'resource_reviews' => 'Resource Reviews',
    ],

    'alert_rules' => [
        'no_prospecting_days' => 7,
        'no_presentations_days' => 14,
        'no_fna_days' => 14,
        'no_followups_days' => 7,
        'pace_behind_percent' => 80,
    ],

    'rank_requirements' => [
        'SA' => ['production' => 10000, 'recruits' => 0, 'licensing' => 100, 'fap' => 100, 'training' => 80],
        'MB' => ['production' => 40000, 'recruits' => 0, 'licensing' => 100, 'fap' => 100, 'training' => 90],
        'SMB' => ['production' => 80000, 'recruits' => 0, 'licensing' => 100, 'fap' => 100, 'training' => 90],
        'AVP' => ['production' => 100000, 'recruits' => 0, 'licensing' => 100, 'fap' => 100, 'training' => 100],
        'SVP' => ['production' => 60000, 'recruits' => 3, 'licensing' => 100, 'fap' => 100, 'training' => 100],
        'EVP' => ['production' => 150000, 'recruits' => 3, 'licensing' => 100, 'fap' => 100, 'training' => 100],
        'EVC' => ['production' => 300000, 'recruits' => 7, 'licensing' => 100, 'fap' => 100, 'training' => 100],
        'SEVC' => ['production' => 500000, 'recruits' => 12, 'licensing' => 100, 'fap' => 100, 'training' => 100],
        'NEVC' => ['production' => 800000, 'recruits' => 18, 'licensing' => 100, 'fap' => 100, 'training' => 100],
    ],

];
