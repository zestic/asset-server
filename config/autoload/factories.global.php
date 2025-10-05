<?php

declare(strict_types=1);

use Application\Factory\Infrastructure\PostgresPDOFactory;

return [
    'dependencies' => [
        'abstract_factories' => [],
        'factories'          => [
            PDO::class => PostgresPDOFactory::class,
        ],
    ],
];
