<?php

declare(strict_types=1);

return [
    'ticket_types' => [
        'bug' => 'Bug / Something broken',
        'troubleshooting' => 'Troubleshooting help',
        'account' => 'Account & login',
        'data' => 'Data issue',
        'performance' => 'Performance / slow',
        'permission' => 'Permission / access',
        'training' => 'Training module',
        'calendar' => 'Calendar',
        'prospect' => 'Prospects / CRM',
        'fna' => 'FNA',
        'goal' => 'Goals & performance',
        'team' => 'Team / hierarchy',
        'cfm_fap' => 'CFM / FAP',
        'general' => 'General question',
        'other' => 'Other',
        'enhancement' => 'Enhancement / wishlist idea',
    ],

    'modules' => [
        'dashboard' => 'Dashboard',
        'login' => 'Login & authentication',
        'profile' => 'My Profile',
        'team_hierarchy' => 'Team hierarchy',
        'downline_tree' => 'Downline tree',
        'org_chart' => 'Org chart',
        'cfm' => 'CFM portal',
        'fap' => 'Field Apprenticeship (FAP)',
        'training' => 'Training academy',
        'licensing' => 'Licensing tracker',
        'prospects_crm' => 'Prospects / CRM',
        'funnel' => 'Prospect funnel',
        'fna' => 'FNA management',
        'calendar' => 'Calendar',
        'booking' => 'Booking & scheduling',
        'tasks' => 'Tasks',
        'goals' => 'Goals & performance',
        'resources' => 'Resource library',
        'notifications' => 'Notifications',
        'reports' => 'Reports',
        'admin' => 'Administration',
        'mobile' => 'Mobile experience',
        'other' => 'Other / not listed',
    ],

    'categories' => [
        'not_loading' => 'Page not loading',
        'button_broken' => 'Button not working',
        'form_not_saving' => 'Form not saving',
        'data_missing' => 'Data missing',
        'data_incorrect' => 'Data incorrect',
        'access_denied' => 'Access denied',
        'error_message' => 'Error message shown',
        'email_not_received' => 'Email not received',
        'upload_issue' => 'Upload issue',
        'scheduling_issue' => 'Scheduling issue',
        'search_broken' => 'Search not working',
        'report_inaccurate' => 'Report inaccurate',
        'layout_issue' => 'Layout / display issue',
        'slow_performance' => 'Slow / performance',
        'other' => 'Other',
    ],

    'user_intent_actions' => [
        'view' => 'View / open something',
        'add' => 'Add new record',
        'edit' => 'Edit existing record',
        'delete' => 'Delete something',
        'submit' => 'Submit a form',
        'upload' => 'Upload a file',
        'schedule' => 'Schedule an event',
        'assign' => 'Assign to someone',
        'report' => 'Run a report',
        'training' => 'Complete training',
        'other' => 'Something else',
    ],

    'user_reported_outcomes' => [
        'nothing' => 'Nothing happened',
        'error_shown' => 'Error message appeared',
        'wrong_info' => 'Wrong information shown',
        'frozen' => 'Screen froze',
        'slow' => 'Very slow response',
        'redirected' => 'Redirected unexpectedly',
        'denied' => 'Access denied',
        'not_saved' => 'Changes not saved',
        'other' => 'Other outcome',
    ],

    'urgency_levels' => [
        'low' => 'Low — can wait',
        'medium' => 'Medium — affects my work',
        'high' => 'High — blocking my work',
        'urgent' => 'Urgent — critical blocker',
    ],

    'impact_levels' => [
        'self' => 'Just me',
        'trainee' => 'My trainee(s)',
        'team' => 'My team',
        'agency' => 'Whole agency',
        'all' => 'Everyone on GWTrack',
        'unknown' => 'Not sure',
    ],

    'frequency_levels' => [
        'once' => 'Happened once',
        'sometimes' => 'Happens sometimes',
        'always' => 'Happens every time',
        'unknown' => 'Not sure',
    ],

    'devices' => [
        'desktop' => 'Desktop computer',
        'laptop' => 'Laptop',
        'tablet' => 'Tablet',
        'mobile' => 'Mobile phone',
        'multiple' => 'Multiple devices',
        'unknown' => 'Not sure',
    ],

    'browsers' => [
        'chrome' => 'Google Chrome',
        'edge' => 'Microsoft Edge',
        'safari' => 'Safari',
        'firefox' => 'Firefox',
        'mobile_browser' => 'Mobile browser',
        'unknown' => 'Not sure',
    ],

    'sla_statuses' => [
        'on_track' => 'On track',
        'at_risk' => 'At risk',
        'overdue' => 'Overdue',
    ],

    'wishlist_statuses' => [
        'submitted' => 'Submitted',
        'under_review' => 'Under review',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
        'planned' => 'Planned',
        'in_development' => 'In development',
        'released' => 'Released',
    ],

    'wishlist_user_priorities' => [
        'low' => 'Nice to have',
        'medium' => 'Would help a lot',
        'high' => 'Really need this',
    ],

    'development_complexities' => [
        'low' => 'Low complexity',
        'medium' => 'Medium complexity',
        'high' => 'High complexity',
    ],

    'business_value_options' => [
        'saves_time' => 'Saves time',
        'reduces_confusion' => 'Reduces confusion',
        'improves_onboarding' => 'Improves onboarding',
        'improves_training' => 'Improves training',
        'improves_reporting' => 'Improves reporting',
        'improves_compliance' => 'Improves compliance',
        'increases_recruiting' => 'Increases recruiting',
        'increases_production' => 'Increases production',
        'better_mobile' => 'Better mobile experience',
        'other' => 'Other value',
    ],

    'urgency_weights' => [
        'urgent' => 40,
        'high' => 30,
        'medium' => 20,
        'low' => 10,
    ],

    'impact_weights' => [
        'all' => 30,
        'agency' => 25,
        'team' => 20,
        'trainee' => 15,
        'self' => 10,
        'unknown' => 5,
    ],

    'frequency_weights' => [
        'always' => 20,
        'sometimes' => 12,
        'once' => 6,
        'unknown' => 4,
    ],

    'wishlist_value_weights' => [
        'high' => 15,
        'medium' => 10,
        'low' => 5,
    ],

    'complexity_weights' => [
        'high' => 15,
        'medium' => 8,
        'low' => 3,
    ],

    'attachment' => [
        'max_bytes' => 10 * 1024 * 1024,
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ],
        'disk' => 'local',
        'directory' => 'support-attachments',
    ],

    'closed_status_slugs' => ['resolved', 'closed'],

    'open_status_slugs' => ['new', 'open', 'awaiting_user', 'in_progress', 'under_review'],
];
