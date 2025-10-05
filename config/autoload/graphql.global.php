<?php

declare(strict_types=1);

return [
    'graphql' => [
        'middleware' => [
            'allowedHeaders' => [
                'application/graphql',
                'application/json',
            ],
        ],
        'schema'     => [
            'isCacheEnabled'    => true,
            'cacheDirectory'    => __DIR__ . '/../../data/cache/graphql',
            'schemaDirectories' => [
                __DIR__ . '/../../resources/graphql',
                __DIR__ . '/../../vendor/zestic/graphql-auth-component/resources/graphql',
            ],
            'parserOptions'     => [],
        ],
        'server'     => [
            'errorsHandler' => function (array $errors, callable $formatter) {
                // TODO: Implement error handling (e.g., Sentry\captureException($error))
                // foreach ($errors as $error) {
                //     // Handle error
                // }

                return array_map($formatter, $errors);
            },
        ],
    ],
];
