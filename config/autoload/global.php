<?php

declare(strict_types=1);

return [
    'debug'  => filter_var(
        getenv('APP_ENV') === 'development',
        FILTER_VALIDATE_BOOLEAN
    ),
    'whoops' => [
        'editor'               => 'vscode',
        'editor_url_pattern'   => 'vscode://file/%file:%line',
        'application_paths'    => [__DIR__ . '/../../'],
        'allowed_ip_addresses' => ['127.0.0.1', '::1', '192.168.*', '10.0.*'],
        'blacklist'            => [
            '_ENV'    => ['DB_PASSWORD', 'AWS_SECRET_KEY', 'JWT_SECRET'],
            '_SERVER' => ['DB_PASSWORD', 'AWS_SECRET_KEY', 'JWT_SECRET'],
        ],
    ],
];
