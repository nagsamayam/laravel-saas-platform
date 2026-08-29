<?php

declare(strict_types=1);

return [

    'host' => env('RABBITMQ_HOST', 'rabbitmq'),

    'port' => (int) env('RABBITMQ_PORT', 5672),

    'username' => env('RABBITMQ_USER', 'saas'),

    'password' => env('RABBITMQ_PASSWORD', 'saas_secret'),

    'vhost' => env('RABBITMQ_VHOST', '/'),

    'exchange' => env('RABBITMQ_EXCHANGE', 'saas'),

    'connection_timeout' => (float) env(
        'RABBITMQ_CONNECTION_TIMEOUT',
        5.0
    ),

    'read_write_timeout' => (float) env(
        'RABBITMQ_READ_WRITE_TIMEOUT',
        5.0
    ),

];
