<?php

return [

    'default' => env('BROADCAST_CONNECTION', 'null'),

    'connections' => [

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER'),
                'host' => env('PUSHER_HOST'),
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [],
        ],

        'pusher_notifications' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_NOTIFICATION_APP_KEY'),
            'secret' => env('PUSHER_NOTIFICATION_APP_SECRET'),
            'app_id' => env('PUSHER_NOTIFICATION_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_NOTIFICATION_APP_CLUSTER'),
                'host' => env('PUSHER_NOTIFICATION_HOST'),
                'port' => env('PUSHER_NOTIFICATION_PORT', 443),
                'scheme' => env('PUSHER_NOTIFICATION_SCHEME', 'https'),
                'useTLS' => env('PUSHER_NOTIFICATION_SCHEME', 'https') === 'https',
            ],
            'client_options' => [],
        ],

        'pusher_chat' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_CHAT_APP_KEY'),
            'secret' => env('PUSHER_CHAT_APP_SECRET'),
            'app_id' => env('PUSHER_CHAT_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_CHAT_APP_CLUSTER'),
                'host' => env('PUSHER_CHAT_HOST'),
                'port' => env('PUSHER_CHAT_PORT', 443),
                'scheme' => env('PUSHER_CHAT_SCHEME', 'https'),
                'useTLS' => env('PUSHER_CHAT_SCHEME', 'https') === 'https',
            ],
            'client_options' => [],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
