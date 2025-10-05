<?php

declare(strict_types=1);

return [
    'db' => [
        'driver'   => 'pdo_pgsql',
        'host'     => getenv('DB_HOST') ?: throw new RuntimeException('DB_HOST environment variable not set'),
        'port'     => getenv('DB_PORT') ?: throw new RuntimeException('DB_PORT environment variable not set'),
        'dbname'   => getenv('DB_NAME') ?: throw new RuntimeException('DB_NAME environment variable not set'),
        'user'     => getenv('DB_USER') ?: throw new RuntimeException('DB_USER environment variable not set'),
        'password' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD environment variable not set'),
        'charset'  => 'utf8',
        'schema'   => getenv('DB_SCHEMA') ?: throw new RuntimeException('DB_SCHEMA environment variable not set'),
    ],
];
