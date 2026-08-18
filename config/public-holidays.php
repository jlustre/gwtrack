<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rolling generation window
    |--------------------------------------------------------------------------
    |
    | Public holiday events are generated automatically for each year in this
    | range (relative to the current calendar year). Adjust years_ahead when
    | you want more future holidays pre-generated.
    |
    */

    'window' => [
        'years_back' => (int) env('PUBLIC_HOLIDAY_YEARS_BACK', 1),
        'years_ahead' => (int) env('PUBLIC_HOLIDAY_YEARS_AHEAD', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Holiday calendars
    |--------------------------------------------------------------------------
    |
    | Each calendar defines declarative rules — dates are computed per year at
    | sync time (not stored as fixed dates in the database seeder).
    |
    | Rule types:
    | - fixed: month/day with optional weekend observation
    | - nth_weekday: e.g. 3rd Monday in January
    | - last_weekday: e.g. last Monday in May
    | - weekday_on_or_before: e.g. Monday on or before May 25
    | - easter_offset: days relative to Easter Sunday (Good Friday = -2)
    |
    */

    'calendars' => [
        'us-public-holidays' => [
            'name' => 'US Public Holidays',
            'color' => '#1D4ED8',
            'icon' => 'flag',
            'sort_order' => 5,
            'event_type' => [
                'slug' => 'us-public-holiday',
                'name' => 'US Public Holiday',
            ],
            'timezone' => 'America/New_York',
            'holidays' => [
                ['name' => "New Year's Day", 'rule' => ['type' => 'fixed', 'month' => 1, 'day' => 1, 'observe' => true]],
                ['name' => 'Martin Luther King Jr. Day', 'rule' => ['type' => 'nth_weekday', 'month' => 1, 'weekday' => 1, 'occurrence' => 3]],
                ['name' => "Presidents' Day", 'rule' => ['type' => 'nth_weekday', 'month' => 2, 'weekday' => 1, 'occurrence' => 3]],
                ['name' => 'Memorial Day', 'rule' => ['type' => 'last_weekday', 'month' => 5, 'weekday' => 1]],
                ['name' => 'Juneteenth', 'rule' => ['type' => 'fixed', 'month' => 6, 'day' => 19, 'observe' => true]],
                ['name' => 'Independence Day', 'rule' => ['type' => 'fixed', 'month' => 7, 'day' => 4, 'observe' => true]],
                ['name' => 'Labor Day', 'rule' => ['type' => 'nth_weekday', 'month' => 9, 'weekday' => 1, 'occurrence' => 1]],
                ['name' => 'Columbus Day', 'rule' => ['type' => 'nth_weekday', 'month' => 10, 'weekday' => 1, 'occurrence' => 2]],
                ['name' => 'Veterans Day', 'rule' => ['type' => 'fixed', 'month' => 11, 'day' => 11, 'observe' => true]],
                ['name' => 'Thanksgiving Day', 'rule' => ['type' => 'nth_weekday', 'month' => 11, 'weekday' => 4, 'occurrence' => 4]],
                ['name' => 'Christmas Day', 'rule' => ['type' => 'fixed', 'month' => 12, 'day' => 25, 'observe' => true]],
            ],
        ],

        'canada-public-holidays' => [
            'name' => 'Canadian Public Holidays',
            'color' => '#DC2626',
            'icon' => 'maple-leaf',
            'sort_order' => 6,
            'event_type' => [
                'slug' => 'canada-public-holiday',
                'name' => 'Canadian Public Holiday',
            ],
            'timezone' => 'America/Toronto',
            'holidays' => [
                ['name' => "New Year's Day", 'rule' => ['type' => 'fixed', 'month' => 1, 'day' => 1, 'observe' => true]],
                ['name' => 'Good Friday', 'rule' => ['type' => 'easter_offset', 'days' => -2]],
                ['name' => 'Victoria Day', 'rule' => ['type' => 'weekday_on_or_before', 'month' => 5, 'day' => 25, 'weekday' => 1]],
                ['name' => 'Canada Day', 'rule' => ['type' => 'fixed', 'month' => 7, 'day' => 1, 'observe' => true]],
                ['name' => 'Labour Day', 'rule' => ['type' => 'nth_weekday', 'month' => 9, 'weekday' => 1, 'occurrence' => 1]],
                ['name' => 'National Day for Truth and Reconciliation', 'rule' => ['type' => 'fixed', 'month' => 9, 'day' => 30, 'observe' => true]],
                ['name' => 'Thanksgiving', 'rule' => ['type' => 'nth_weekday', 'month' => 10, 'weekday' => 1, 'occurrence' => 2]],
                ['name' => 'Remembrance Day', 'rule' => ['type' => 'fixed', 'month' => 11, 'day' => 11, 'observe' => true]],
                ['name' => 'Christmas Day', 'rule' => ['type' => 'fixed', 'month' => 12, 'day' => 25, 'observe' => true]],
                ['name' => 'Boxing Day', 'rule' => ['type' => 'fixed', 'month' => 12, 'day' => 26, 'observe' => true]],
            ],
        ],
    ],
];
