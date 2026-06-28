<?php

return [
    'proxy' => [
        'through' => env('SERVICES_PROXY_THROUGH'),
        'retry' => [
            'times' => env('SERVICES_PROXY_RETRY_TIMES', 10),
            'delay' => env('SERVICES_PROXY_RETRY_DELAY', 0),
        ],
    ],
    'geometry_dash_proxy' => [
        'upstream' => [
            'base' => env('SERVICES_GEOMETRY_DASH_PROXY_UPSTREAM_BASE', 'https://www.boomlings.com/database'),
        ],
    ],
];
