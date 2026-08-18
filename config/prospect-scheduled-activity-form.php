<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Quick schedule shortcuts (appointment_types.slug)
    |--------------------------------------------------------------------------
    */
    'quick_schedule_types' => [
        'discovery-call' => 'Discovery',
        'financial-needs-analysis' => 'FNA',
        'product-presentation' => 'Presentation',
        'follow-up-meeting' => 'Follow-Up',
        'career-overview' => 'Career',
        'mentor-assisted-call' => 'Mentor Call',
        'application-review' => 'App Review',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module sync defaults
    |--------------------------------------------------------------------------
    */
    'sync' => [
        'calendar' => true,
        'prep_task' => true,
        'prep_task_title' => 'Prepare for scheduled touchpoint: :prospect',
        'prep_task_offset_days' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default purpose / location dropdown options
    |--------------------------------------------------------------------------
    */
    'default_purpose_options' => [
        'Discovery conversation',
        'FNA review',
        'Product overview',
        'Protection review',
        'Recruiting interview',
        'Career overview',
        'Follow-up meeting',
        'Application review',
        'Mentor-assisted call',
    ],

    'default_location_options' => [
        'Phone call',
        'Office — in person',
        'Client home visit',
        'Zoom — team room',
        'Zoom — personal link',
        'Google Meet',
        'Microsoft Teams',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled activity form fields
    |--------------------------------------------------------------------------
    | payload_key maps to ProspectScheduleActivityService attribute names.
    */
    'fields' => [
        [
            'key' => 'appointment_type_id',
            'payload_key' => 'appointment_type_id',
            'label' => 'Activity Type',
            'type' => 'select',
            'options_key' => 'appointment_types',
            'required' => true,
            'live' => true,
            'col_span' => 2,
            'help' => 'Choose the planned touchpoint. This maps to your calendar event type.',
        ],
        [
            'key' => 'scheduled_at',
            'payload_key' => 'scheduled_at',
            'label' => 'Date & Time',
            'type' => 'datetime',
            'required' => true,
            'col_span' => 1,
            'help' => 'When this activity is planned to happen.',
        ],
        [
            'key' => 'duration_minutes',
            'payload_key' => 'duration_minutes',
            'label' => 'Duration (minutes)',
            'type' => 'number',
            'min' => 15,
            'max' => 480,
            'default' => 60,
            'col_span' => 1,
        ],
        [
            'key' => 'purpose',
            'payload_key' => 'purpose',
            'label' => 'Purpose / Agenda',
            'type' => 'select_creatable',
            'options_key' => 'purpose_options',
            'options_category' => 'purpose',
            'create_field_key' => 'purpose_new',
            'create_option_label' => 'Add new purpose…',
            'create_placeholder' => 'Describe the purpose or agenda…',
            'placeholder' => 'Select a purpose',
            'col_span' => 2,
        ],
        [
            'key' => 'location_or_link',
            'payload_key' => 'location_or_link',
            'label' => 'Location or Meeting Link',
            'type' => 'select_creatable',
            'options_key' => 'location_options',
            'options_category' => 'location',
            'create_field_key' => 'location_or_link_new',
            'create_option_label' => 'Add new location or link…',
            'create_placeholder' => 'Office address, Zoom URL, phone…',
            'placeholder' => 'Select a location or link',
            'col_span' => 2,
        ],
        [
            'key' => 'assigned_helper_id',
            'payload_key' => 'assigned_helper_id',
            'label' => 'Helper / Mentor',
            'type' => 'select',
            'options_key' => 'team_helpers',
            'placeholder' => 'Optional — invite a helper',
            'col_span' => 2,
        ],
        [
            'key' => 'notes',
            'payload_key' => 'notes',
            'label' => 'Prep Notes',
            'type' => 'textarea',
            'rows' => 3,
            'placeholder' => 'Talking points, materials to bring, context for your team…',
            'col_span' => 2,
        ],
        [
            'key' => 'create_prep_task',
            'payload_key' => 'create_prep_task',
            'label' => 'Create prep task',
            'type' => 'checkbox',
            'default' => true,
            'col_span' => 1,
            'help' => 'Adds a linked task in Task Manager before the scheduled time.',
        ],
        [
            'key' => 'sync_calendar',
            'payload_key' => 'sync_calendar',
            'label' => 'Sync to calendar',
            'type' => 'checkbox',
            'default' => true,
            'col_span' => 1,
            'help' => 'Creates or updates a calendar event for this prospect.',
        ],
    ],
];
