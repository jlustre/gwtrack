<?php

return [
    'activity_next_action' => [
        'followup_type' => 'activity_next_action',
        'priority' => 'medium',
        'create_task' => true,
        'create_calendar_event' => true,
        'calendar_event_type' => 'crm-follow-up',
        'calendar_duration_minutes' => 30,
        'title_prefix' => 'Follow up',
    ],

    'communication_next_action' => [
        'followup_type' => 'communication_next_action',
        'priority' => 'medium',
        'create_task' => true,
        'create_calendar_event' => true,
        'calendar_event_type' => 'crm-follow-up',
        'calendar_duration_minutes' => 30,
        'title_prefix' => 'Follow up',
    ],

    'stage_entered' => [
        'create_task_from_template' => true,
        'create_calendar_event_when_timed' => true,
        'calendar_event_type' => 'crm-follow-up',
        'calendar_duration_minutes' => 30,
    ],

    'missed_appointment' => [
        'followup_type' => 'missed_appointment',
        'priority' => 'high',
        'create_task' => true,
        'offset_days' => 1,
        'notes' => 'Appointment was missed. Reschedule and confirm the next meeting time.',
    ],
];
