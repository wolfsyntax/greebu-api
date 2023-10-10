<?php

return [
    'apps' => [
        [
            'id' => env('PUSHER_APP_ID'),
            'name' => env('APP_NAME'),
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'enable_client_messages' => false,
            'enable_statistics' => true,
        ],
    ]
];
