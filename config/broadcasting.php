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
                'host' => env('PUSHER_HOST', 'api-'.env('PUSHER_APP_CLUSTER', 'ap2').'.pusher.com'),
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
                'host' => env('PUSHER_NOTIFICATION_HOST', 'api-'.env('PUSHER_NOTIFICATION_APP_CLUSTER', 'ap2').'.pusher.com'),
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
                'host' => env('PUSHER_CHAT_HOST', 'api-'.env('PUSHER_CHAT_APP_CLUSTER', 'ap2').'.pusher.com'),
                'port' => env('PUSHER_CHAT_PORT', 443),
                'scheme' => env('PUSHER_CHAT_SCHEME', 'https'),
                'useTLS' => env('PUSHER_CHAT_SCHEME', 'https') === 'https',
            ],
            'client_options' => [],
        ],

        'pusher_insights' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_INSIGHTS_APP_KEY'),
            'secret' => env('PUSHER_INSIGHTS_APP_SECRET'),
            'app_id' => env('PUSHER_INSIGHTS_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_INSIGHTS_APP_CLUSTER'),
                'host' => env('PUSHER_INSIGHTS_HOST', 'api-'.env('PUSHER_INSIGHTS_APP_CLUSTER', 'ap2').'.pusher.com'),
                'port' => env('PUSHER_INSIGHTS_PORT', 443),
                'scheme' => env('PUSHER_INSIGHTS_SCHEME', 'https'),
                'useTLS' => env('PUSHER_INSIGHTS_SCHEME', 'https') === 'https',
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
