<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:4200', 
        'http://51.178.87.234',
        'http://localhost:57128',
        'http://localhost:57129',
        'http://127.0.0.1:57128',
        'http://127.0.0.1:57129',
        'http://localhost',
        'http://127.0.0.1',
        'https://localhost',
        'https://127.0.0.1',
        'http://localhost:58569',
        'https://syndify.nomade-cloud.com:8102',
        'http://51.178.87.234:8021'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, 

];