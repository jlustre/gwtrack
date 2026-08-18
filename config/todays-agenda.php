<?php

use App\Services\TodaysAgenda\Providers\AppointmentsSectionProvider;
use App\Services\TodaysAgenda\Providers\CalendarEventsSectionProvider;
use App\Services\TodaysAgenda\Providers\ChecklistStepsSectionProvider;
use App\Services\TodaysAgenda\Providers\FollowUpsSectionProvider;
use App\Services\TodaysAgenda\Providers\PhoneCallsSectionProvider;
use App\Services\TodaysAgenda\Providers\TasksDueTodaySectionProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Today's agenda section providers
    |--------------------------------------------------------------------------
    | Register section providers to extend the daily agenda popup. Each provider
    | must implement App\Contracts\TodaysAgendaSectionProvider.
    */
    'providers' => [
        TasksDueTodaySectionProvider::class,
        PhoneCallsSectionProvider::class,
        AppointmentsSectionProvider::class,
        CalendarEventsSectionProvider::class,
        ChecklistStepsSectionProvider::class,
        FollowUpsSectionProvider::class,
    ],

    'show_empty_sections_in_modal' => true,

    'checklist_type_labels' => [
        'onboarding' => 'Onboarding',
        'licensing' => 'Licensing',
        'fap' => 'Field Apprenticeship',
        'cfm-training' => 'CFM Training',
    ],
];
