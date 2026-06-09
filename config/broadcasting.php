<?php

return [

    'default' => env('BROADCAST_CONNECTION', env('BROADCAST_DRIVER', 'reverb')),

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST', env('PUSHER_HOST', '127.0.0.1')),
                'port' => (int) env('REVERB_PORT', env('PUSHER_PORT', 8080)),
                'scheme' => env('REVERB_SCHEME', env('PUSHER_SCHEME', 'http')),
                'useTLS' => env('REVERB_SCHEME', env('PUSHER_SCHEME', 'http')) === 'https',
            ],
            'client_options' => [],
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'host' => env('PUSHER_HOST'),
                'port' => (int) env('PUSHER_PORT', 6001),
                'scheme' => env('PUSHER_SCHEME', 'http'),
                'useTLS' => env('PUSHER_SCHEME', 'http') === 'https',
            ],
        ],

        'ably' => [
            'driver' => 'ably',
            'key' => env('ABLY_KEY'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
