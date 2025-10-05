<?php

declare(strict_types=1);

return [
    'paths' => [
        'migrations' => [
            // GraphQL Auth Component migrations
            '%%PHINX_CONFIG_DIR%%/vendor/zestic/graphql-auth-component/resources/db/migrations/postgres',
            // Communication Component migrations  
            '%%PHINX_CONFIG_DIR%%/vendor/zestic/communication-component/db/migrations/postgres',
            // Local project migrations (if any)
            '%%PHINX_CONFIG_DIR%%/resources/db/migrations',
        ],
        'seeds' => '%%PHINX_CONFIG_DIR%%/resources/db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'production' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST') ?: throw new RuntimeException('DB_HOST environment variable not set'),
            'name' => getenv('DB_NAME') ?: throw new RuntimeException('DB_NAME environment variable not set'),
            'user' => getenv('DB_USER') ?: throw new RuntimeException('DB_USER environment variable not set'),
            'pass' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD environment variable not set'),
            'port' => (int) (getenv('DB_PORT') ?: throw new RuntimeException('DB_PORT environment variable not set')),
            'charset' => 'utf8',
            'schema' => getenv('DB_SCHEMA') ?: throw new RuntimeException('DB_SCHEMA environment variable not set'),
        ],
        'development' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST') ?: throw new RuntimeException('DB_HOST environment variable not set'),
            'name' => getenv('DB_NAME') ?: throw new RuntimeException('DB_NAME environment variable not set'),
            'user' => getenv('DB_USER') ?: throw new RuntimeException('DB_USER environment variable not set'),
            'pass' => getenv('DB_PASSWORD') ?: throw new RuntimeException('DB_PASSWORD environment variable not set'),
            'port' => (int) (getenv('DB_PORT') ?: throw new RuntimeException('DB_PORT environment variable not set')),
            'charset' => 'utf8',
            'schema' => getenv('DB_SCHEMA') ?: throw new RuntimeException('DB_SCHEMA environment variable not set'),
        ],
        'testing' => [
            'adapter' => 'pgsql',
            'host' => getenv('DB_HOST') ?: 'localhost',
            'name' => getenv('DB_NAME') ?: 'zestic_api_test',
            'user' => getenv('DB_USER') ?: 'zestic',
            'pass' => getenv('DB_PASSWORD') ?: 'password1',
            'port' => (int) (getenv('DB_PORT') ?: 5432),
            'charset' => 'utf8',
            'schema' => getenv('DB_SCHEMA') ?: 'public',
        ],
    ],
    'version_order' => 'creation',
];
