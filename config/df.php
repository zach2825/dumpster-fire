<?php

return [
    'username'              => env('DF_USERNAME'),
    'email'                 => env('DF_EMAIL'),
    'personal_access_token' => env('DF_PERSONAL_ACCESS_TOKEN'),

    'organization' => env('DF_ORGANIZATION'),
    'project'      => env('DF_PROJECT'),

    'transition' => [
        'ready'       => env('DF_READY', 'Ready'),
        'discovery'   => env('DF_DISCOVERY', 'Discovery'),
        'development' => env('DF_DEVELOPMENT', 'Development'),
        'review'      => env('DF_REVIEW', 'Code Review'),
    ],
];
