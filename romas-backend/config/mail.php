<?php

return [

    'default' => env('MAIL_MAILER', 'smtp'),

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme'   => env('MAIL_SCHEME'),
            'url'      => env('MAIL_URL'),
            'host'     => env('MAIL_HOST', '127.0.0.1'),
            'port'     => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout'  => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
            'encryption' => env('MAIL_ENCRYPTION'),
        ],

        // Transport Gmail API (OAuth 2.0)
        'gmail' => [
            'transport'     => 'gmail',
            'username'      => env('MAIL_USERNAME'),
            'client_id'     => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'refresh_token' => env('GOOGLE_REFRESH_TOKEN'),
        ],

        // Autres transports (laissés pour compatibilité)
        'resend' => [
            'transport' => 'resend',
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name'    => env('MAIL_FROM_NAME', 'Example'),
    ],

    'markdown' => [
        'theme' => 'default',
        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

];