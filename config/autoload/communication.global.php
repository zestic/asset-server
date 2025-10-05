<?php

declare(strict_types=1);

return [
    'communication' => [
        'routes'  => [
            'email' => 'bus',
        ],
        'channel' => [
            'email' => [
                'from' => 'notifications@zestic.com',
            ],
        ],
    ],
];
