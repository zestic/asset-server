<?php

declare(strict_types=1);

use Mezzio\Cors\Configuration\ConfigurationInterface;

return [
    ConfigurationInterface::CONFIGURATION_IDENTIFIER => [
        'allowed_headers'     => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept',
            'Origin',
            'X-Client-ID',
        ],
        'allowed_max_age'     => '86400',
        'credentials_allowed' => false,
        'exposed_headers'     => [],
    ],
];
