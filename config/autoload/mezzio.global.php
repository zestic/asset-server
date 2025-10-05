<?php

declare(strict_types=1);

use Laminas\ConfigAggregator\ConfigAggregator;

return [
    // Toggle the configuration cache. Set this to boolean false, or remove the
    // directive, to disable configuration caching. Toggling development mode
    // will also disable it by default; clear the configuration cache using
    // `composer clear-config-cache`.
    ConfigAggregator::ENABLE_CACHE => true,

    // Enable debugging; typically used to provide debugging information within templates.
    'debug'  => false,
    'mezzio' => [
        // For API-only applications, disable template-based error handling
        // This will make the error handler return JSON responses instead
        'error_handler' => [
            // Comment out template configurations for API-only responses
            // 'template_404'   => 'error::404',
            // 'template_error' => 'error::error',
        ],
    ],
];
