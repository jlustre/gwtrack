<?php

return [
    'statuses' => [
        'draft' => 'Draft',
        'ready_for_review' => 'Ready for Review',
        'submitted_to_cfm' => 'Submitted to CFM',
        'under_cfm_review' => 'Under CFM Review',
        'revision_requested' => 'Revision Requested',
        'approved_by_cfm' => 'Approved by CFM',
        'scheduled_for_client_review' => 'Scheduled for Client Review',
        'presented_to_prospect' => 'Presented to Prospect',
        'follow_up_needed' => 'Follow-Up Needed',
        'converted_to_application' => 'Converted to Application',
        'closed' => 'Closed',
        'archived' => 'Archived',
    ],

    'transitions' => [
        'draft' => ['ready_for_review', 'archived'],
        'ready_for_review' => ['submitted_to_cfm', 'draft', 'archived'],
        'submitted_to_cfm' => ['under_cfm_review', 'archived'],
        'under_cfm_review' => ['approved_by_cfm', 'revision_requested', 'archived'],
        'revision_requested' => ['draft', 'ready_for_review', 'archived'],
        'approved_by_cfm' => ['scheduled_for_client_review', 'presented_to_prospect', 'follow_up_needed', 'closed', 'archived'],
        'scheduled_for_client_review' => ['presented_to_prospect', 'approved_by_cfm', 'closed', 'archived'],
        'presented_to_prospect' => ['follow_up_needed', 'converted_to_application', 'closed', 'archived'],
        'follow_up_needed' => ['converted_to_application', 'scheduled_for_client_review', 'closed', 'archived'],
        'converted_to_application' => ['closed', 'archived'],
        'closed' => ['archived'],
        'archived' => [],
    ],

    'cfm_visible_statuses' => [
        'submitted_to_cfm',
        'under_cfm_review',
        'revision_requested',
        'approved_by_cfm',
        'scheduled_for_client_review',
        'presented_to_prospect',
        'follow_up_needed',
        'converted_to_application',
        'closed',
        'archived',
    ],

    'prospect_fna_status_map' => [
        'draft' => 'not_started',
        'ready_for_review' => 'not_started',
        'revision_requested' => 'not_started',
        'submitted_to_cfm' => 'not_started',
        'under_cfm_review' => 'not_started',
        'approved_by_cfm' => 'scheduled',
        'scheduled_for_client_review' => 'scheduled',
        'presented_to_prospect' => 'completed',
        'follow_up_needed' => 'completed',
        'converted_to_application' => 'completed',
        'closed' => 'completed',
        'archived' => 'not_started',
    ],

    'goal_options' => [
        'income_protection' => 'Income Protection',
        'mortgage_protection' => 'Mortgage Protection',
        'final_expense' => 'Final Expense',
        'education_funding' => 'Education Funding',
        'retirement_planning' => 'Retirement Planning',
        'wealth_building' => 'Wealth Building',
        'debt_elimination' => 'Debt Elimination',
        'estate_planning' => 'Estate Planning',
        'business_continuation' => 'Business Continuation',
        'tax_strategies' => 'Tax Strategies',
        'legacy_planning' => 'Legacy Planning',
    ],

    'dime_defaults' => [
        'income_replacement_years' => 10,
        'inflation_rate' => 0.03,
        'education_inflation_rate' => 0.05,
        'education_cost_per_child' => 100000,
        'coverage_range_multiplier' => [0.9, 1.1],
    ],

    'completeness_threshold' => 60,

    'wizard_steps' => [
        1 => 'Client Information',
        2 => 'Household',
        3 => 'Income',
        4 => 'Debt',
        5 => 'Assets',
        6 => 'Insurance',
        7 => 'Goals',
        8 => 'Risk Assessment',
        9 => 'Summary',
    ],

    'required_fields' => [
        'client_name',
        'client_email',
        'annual_income',
        'main_financial_concern',
    ],

    'preferred_contact_methods' => [
        'phone' => 'Phone',
        'email' => 'Email',
        'text' => 'Text Message',
        'video_call' => 'Video Call',
    ],

    'client_portal_step_required_fields' => [
        1 => [
            'client_name',
            'client_email',
            'client_phone',
            'date_of_birth',
            'gender',
            'marital_status',
            'country',
            'state_province',
            'preferred_contact_method',
            'best_contact_time',
        ],
    ],

    'step_field_map' => [
        1 => ['client_name', 'client_email', 'client_phone', 'date_of_birth', 'occupation', 'city', 'state_province', 'country'],
        2 => ['spouse_partner_name', 'household_income', 'household_expenses', 'children_count'],
        3 => ['annual_income', 'monthly_income'],
        4 => ['mortgage_balance', 'credit_card_debt', 'total_debt'],
        5 => ['checking_savings', 'retirement_accounts', 'emergency_fund'],
        6 => ['existing_life_insurance_amount', 'term_coverage'],
        7 => ['selected_goals'],
        8 => ['main_financial_concern', 'urgency_level', 'risk_tolerance'],
        9 => ['main_needs_identified', 'recommended_next_action', 'associate_recommendation'],
    ],

    'dime_disclaimer' => 'This analysis is for educational and planning purposes only and should not be considered a final recommendation. Product suitability and recommendations must follow applicable compliance, licensing, and company guidelines.',

    'checklist_item_links' => [
        'phase_5_financial-needs-analysis-fna' => [
            'route' => 'team.fna.dashboard',
            'label' => 'Open FNA Management',
        ],
        'phase_10_fna-assessment' => [
            'route' => 'team.fna.cfm.review-queue',
            'label' => 'Review trainee FNAs',
        ],
    ],

    'review_sla_hours' => 48,

    'analytics_chart_colors' => [
        'primary' => '#84BB41',
        'secondary' => '#2D3E50',
        'accent' => '#74A539',
        'track' => '#E2E8F0',
    ],

    'approved_statuses' => [
        'approved_by_cfm',
        'scheduled_for_client_review',
        'presented_to_prospect',
        'follow_up_needed',
        'converted_to_application',
        'closed',
    ],

    'awaiting_review_statuses' => [
        'submitted_to_cfm',
        'under_cfm_review',
    ],

    'analytics_metrics' => [
        'total_fnas' => 'Total FNAs',
        'draft_fnas' => 'Draft FNAs',
        'submitted_fnas' => 'Submitted FNAs',
        'approved_fnas' => 'Approved FNAs',
        'dime_completed' => 'DIME Completed',
        'avg_protection_gap' => 'Avg Protection Gap',
    ],

    'calendar_event_types' => [
        ['name' => 'FNA Review with CFM', 'slug' => 'fna-cfm-review'],
        ['name' => 'Client FNA Meeting', 'slug' => 'fna-client-meeting'],
        ['name' => 'Financial Review', 'slug' => 'fna-financial-review'],
        ['name' => 'Protection Gap Review', 'slug' => 'fna-protection-gap-review'],
        ['name' => 'FNA Follow-Up', 'slug' => 'fna-follow-up'],
        ['name' => 'Policy Review', 'slug' => 'fna-policy-review'],
    ],

    'task_templates' => [
        'draft_created' => [
            'title' => 'Complete FNA draft for {client}',
            'priority' => 'medium',
            'offset_days' => 3,
        ],
        'ready_for_review' => [
            'title' => 'Submit FNA to CFM for {client}',
            'priority' => 'high',
            'offset_days' => 1,
        ],
        'submitted' => [
            'title' => 'Review FNA: {trainee} — {client}',
            'priority' => 'high',
            'offset_days' => 2,
            'assignee' => 'cfm',
        ],
        'revision_requested' => [
            'title' => 'Revise FNA for {client}',
            'priority' => 'urgent',
            'offset_days' => 2,
        ],
        'approved' => [
            'title' => 'Schedule client FNA review for {client}',
            'priority' => 'high',
            'offset_days' => 3,
        ],
        'meeting_scheduled' => [
            'title' => 'Prepare for client FNA meeting — {client}',
            'priority' => 'high',
            'offset_days' => 1,
        ],
    ],

    'client_portal' => [
        'invite_expiry_days' => 30,
        'session_ttl_minutes' => 120,
        'security_code_length' => 6,
        // Licensed agents may invite prospects, downline members, trainees, and CFM apprentices (including crossline).
        'member_recipient_enabled' => true,
    ],

    'ai' => [
        'enabled' => env('FNA_AI_ENABLED', true),
        'use_llm' => env('FNA_AI_USE_LLM', false),
        'compliance_notice' => 'AI-generated suggestions are for coaching and planning support only. They are not compliance-approved recommendations. Review all content before sharing with clients or CFMs.',
        'features' => [
            'completeness_checker' => true,
            'protection_gap_summary' => true,
            'meeting_prep' => true,
        ],
        'completeness_rules' => [
            'missing_dime' => [
                'priority' => 'high',
                'message' => 'Complete the DIME analysis to quantify protection needs before CFM review.',
                'action' => 'open_dime_tab',
                'condition' => 'dime_not_completed',
            ],
            'no_goals_selected' => [
                'priority' => 'high',
                'message' => 'Select at least one financial goal so recommendations align with client priorities.',
                'action' => 'complete_goals_step',
                'condition' => 'no_goals',
            ],
            'high_urgency_no_concern' => [
                'priority' => 'high',
                'message' => 'Urgency is marked high but no main financial concern is documented — clarify the client\'s primary worry.',
                'action' => 'complete_risk_step',
                'condition' => 'high_urgency_no_concern',
            ],
            'debt_without_income' => [
                'priority' => 'high',
                'message' => 'Debt details are entered without annual income — income is required to assess protection needs.',
                'action' => 'complete_income_step',
                'condition' => 'debt_without_income',
            ],
            'missing_risk_assessment' => [
                'priority' => 'medium',
                'message' => 'Risk assessment is incomplete — document main concern, urgency, and risk tolerance.',
                'action' => 'complete_risk_step',
                'condition' => 'missing_risk_assessment',
            ],
            'missing_contact_info' => [
                'priority' => 'medium',
                'message' => 'Client contact details are incomplete — add email and phone for follow-up.',
                'action' => 'complete_client_step',
                'condition' => 'missing_contact_info',
            ],
            'below_completeness_threshold' => [
                'priority' => 'medium',
                'message' => 'FNA completeness is below the minimum threshold for CFM submission.',
                'action' => 'review_missing_sections',
                'condition' => 'below_completeness_threshold',
            ],
            'missing_coverage_info' => [
                'priority' => 'medium',
                'message' => 'Existing insurance coverage is not documented — add current life insurance amounts.',
                'action' => 'complete_insurance_step',
                'condition' => 'missing_coverage_info',
            ],
            'missing_summary_fields' => [
                'priority' => 'low',
                'message' => 'Summary fields are incomplete — document main needs and recommended next action.',
                'action' => 'complete_summary_step',
                'condition' => 'missing_summary_fields',
            ],
        ],
    ],
];
