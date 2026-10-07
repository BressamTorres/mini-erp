<?php

return [
    'admin' => [
        'enabled' => env('APP_ENV', 'production') === 'local',
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],
];
