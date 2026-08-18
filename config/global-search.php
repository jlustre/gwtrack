<?php

return [
    'min_query_length' => 2,

    'suggest_limit' => 5,

    'results_limit' => 8,

    'sections' => [
        'members' => [
            'label' => 'Team members',
            'permission' => 'view own team',
        ],
        'prospects' => [
            'label' => 'Prospects',
            'permission' => 'manage prospects|view shared prospects',
        ],
        'resources' => [
            'label' => 'Resources',
            'permission' => null,
        ],
        'videos' => [
            'label' => 'Videos',
            'permission' => null,
        ],
        'training' => [
            'label' => 'Training',
            'permission' => null,
        ],
        'tasks' => [
            'label' => 'Tasks',
            'permission' => null,
        ],
        'events' => [
            'label' => 'Events',
            'permission' => 'view calendar',
        ],
        'announcements' => [
            'label' => 'Announcements',
            'permission' => 'view announcements|manage announcements',
        ],
    ],
];
