<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Transactional seeding default
    |--------------------------------------------------------------------------
    |
    | When true, DatabaseSeeder runs transactional (demo/fixture) seeders after
    | structural seeders. Override per run with --seed-transactional or
    | --no-seed-transactional on migrate:fresh, migrate:refresh, or db:seed.
    |
    */

    'run_transactional' => env('SEED_TRANSACTIONAL', false),

    /*
    |--------------------------------------------------------------------------
    | Structural seeders
    |--------------------------------------------------------------------------
    |
    | Reference data, roles, templates, and configuration required in every
    | environment (including production after migrate --seed).
    |
    */

    'structural' => [
        Database\Seeders\RankSeeder::class,
        Database\Seeders\RankRequirementSeeder::class,
        Database\Seeders\RolePermissionSeeder::class,
        Database\Seeders\EmailTemplateSeeder::class,
        Database\Seeders\EmailTemplateTokenSeeder::class,
        Database\Seeders\CountrySeeder::class,
        Database\Seeders\StateProvinceSeeder::class,
        Database\Seeders\TimezoneSeeder::class,
        Database\Seeders\TeamSeeder::class,
        Database\Seeders\ChecklistTypeSeeder::class,
        Database\Seeders\ChecklistInstructionSeeder::class,
        Database\Seeders\ChecklistSeeder::class,
        Database\Seeders\ProspectLookupSeeder::class,
        Database\Seeders\ProspectFunnelSeeder::class,
        Database\Seeders\CrmFunnelSeeder::class,
        Database\Seeders\UsersSeeder::class,
        Database\Seeders\SystemTaskAssignorSeeder::class,
        Database\Seeders\PublicHolidayCalendarSeeder::class,
        Database\Seeders\ProfileCompletionFieldSeeder::class,
        Database\Seeders\GoalCategorySeeder::class,
        Database\Seeders\TaskCategorySeeder::class,
        Database\Seeders\TaskSeeder::class,
        Database\Seeders\GoalTemplateSeeder::class,
        Database\Seeders\GoalBadgeSeeder::class,
        Database\Seeders\CfmEffectivenessSeeder::class,
        Database\Seeders\ComplianceLifecycleSeeder::class,
        Database\Seeders\SupportModuleSeeder::class,
        Database\Seeders\NotificationConfigSeeder::class,
        Database\Seeders\RecognitionBadgeSeeder::class,
        Database\Seeders\ResourceDocumentSeeder::class,
        Database\Seeders\ResourceVideoSeeder::class,
        Database\Seeders\TrainingAcademySeeder::class,
        Database\Seeders\DailyQuoteSeeder::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transactional seeders
    |--------------------------------------------------------------------------
    |
    | Demo users, sample records, and module fixtures for local/staging only.
    |
    */

    'transactional' => [
        Database\Seeders\TaskScenarioSeeder::class,
        Database\Seeders\TaskManagementSeeder::class,
        Database\Seeders\DownlineManagementSeeder::class,
        Database\Seeders\CfmManagementSeeder::class,
        Database\Seeders\CfmEvaluationDemoSeeder::class,
        Database\Seeders\ProspectDemoSeeder::class,
        Database\Seeders\CrmDemoSeeder::class,
        Database\Seeders\CalendarModuleSeeder::class,
        Database\Seeders\MessagingModuleSeeder::class,
        Database\Seeders\UserCalendarPreferenceSeeder::class,
        Database\Seeders\BookingSchedulingSeeder::class,
        Database\Seeders\NotificationsSeeder::class,
        // Database\Seeders\CommunicationHubDemoSeeder::class,
    ],

];
