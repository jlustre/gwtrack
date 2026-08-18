<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quick activity shortcuts
    |--------------------------------------------------------------------------
    | Keys must exist in config('prospects.activity_types').
    */
    'quick_activity_types' => [
        'phone_call' => 'Phone',
        'text_message' => 'Text',
        'email' => 'Email',
        'zoom_meeting' => 'Zoom',
        'in_person_meeting' => 'In Person',
        'presentation' => 'Presentation',
        'follow_up' => 'Follow-Up',
        'policy_review' => 'Policy Review',
        'recruitment_meeting' => 'Recruitment',
        'financial_review' => 'Financial',
        'referral_request' => 'Referral',
    ],

    /*
    |--------------------------------------------------------------------------
    | Outcome suggestions (datalist)
    |--------------------------------------------------------------------------
    */
    'outcome_suggestions' => 'prospects.contacted_outcomes',

    /*
    |--------------------------------------------------------------------------
    | Activity log form fields
    |--------------------------------------------------------------------------
    | Add or reorder fields here to extend the activity form across the app.
    | Supported types: select, text, textarea, datetime, number.
    | payload_key maps to ProspectFunnelService::logActivity() attribute names.
    | api_key overrides REST JSON field name when it differs from payload_key.
    */
    'fields' => [
        [
            'key' => 'activity_type',
            'payload_key' => 'activity_type',
            'label' => 'Activity Type',
            'type' => 'select',
            'options_key' => 'activity_types',
            'required' => true,
            'live' => true,
            'col_span' => 2,
            'help' => 'Choose the touchpoint you completed or attempted.',
        ],
        [
            'key' => 'activity_subject',
            'payload_key' => 'subject',
            'label' => 'Subject / Title',
            'type' => 'text',
            'placeholder' => 'Email subject, meeting topic, presentation name…',
            'col_span' => 2,
        ],
        [
            'key' => 'activity_occurred_at',
            'payload_key' => 'occurred_at',
            'label' => 'Date & Time',
            'type' => 'datetime',
            'required' => true,
            'col_span' => 1,
        ],
        [
            'key' => 'duration_minutes',
            'payload_key' => 'duration_minutes',
            'label' => 'Duration (minutes)',
            'type' => 'number',
            'min' => 1,
            'max' => 600,
            'placeholder' => 'Optional',
            'col_span' => 1,
        ],
        [
            'key' => 'pipeline_stage_id',
            'payload_key' => 'pipeline_stage_id',
            'label' => 'Pipeline Stage',
            'type' => 'select',
            'options_key' => 'pipeline_stages',
            'placeholder' => 'Keep current stage',
            'col_span' => 2,
            'help' => 'Optionally move the prospect to a new stage when logging this activity.',
        ],
        [
            'key' => 'activity_outcome',
            'payload_key' => 'outcome',
            'label' => 'Outcome',
            'type' => 'text',
            'placeholder' => 'Connected, left voicemail, booked appointment…',
            'suggestions_key' => 'outcome_suggestions',
            'col_span' => 2,
        ],
        [
            'key' => 'activity_notes',
            'payload_key' => 'notes',
            'label' => 'Notes',
            'type' => 'textarea',
            'rows' => 3,
            'placeholder' => 'Conversation summary, objections, context for your team…',
            'col_span' => 2,
        ],
        [
            'key' => 'activity_next_action',
            'payload_key' => 'next_action',
            'label' => 'Next Action',
            'type' => 'text',
            'max' => 1000,
            'placeholder' => 'Send quote, schedule FNA, confirm appointment…',
            'col_span' => 1,
        ],
        [
            'key' => 'activity_next_follow_up_at',
            'payload_key' => 'next_follow_up_at',
            'label' => 'Next Follow-Up',
            'type' => 'datetime',
            'col_span' => 1,
        ],
    ],
];
