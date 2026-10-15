<?php

return [
    'admin' => [
        'enabled' => env('APP_ENV', 'production') === 'local',
        'name' => env('ADMIN_NAME', 'Administrador'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
        'business' => [
            'trade_name' => env('ADMIN_BUSINESS_TRADE_NAME', 'Empresa Local'),
            'legal_name' => env('ADMIN_BUSINESS_LEGAL_NAME', 'Empresa Local Ltda.'),
            'document' => env('ADMIN_BUSINESS_DOCUMENT', '11222333000181'),
            'phone' => env('ADMIN_BUSINESS_PHONE', '11999999999'),
            'email' => env('ADMIN_BUSINESS_EMAIL', 'empresa@example.com'),
        ],
    ],
];
