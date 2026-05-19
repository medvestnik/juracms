<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Jura CMS',
        'url' => '',
        'env' => 'production',
        'debug' => false,
        'key' => '',
        'installed' => false,
        'version' => '0.1.0-alpha',
    ],

    'database' => [
        'connection' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'database' => '',
        'username' => '',
        'password' => '',
        'prefix' => 'jura_',
        'charset' => 'utf8mb4',
    ],

    'security' => [
        'install_reset_token' => '',
        'full_reset_allowed' => false,
    ],
];
