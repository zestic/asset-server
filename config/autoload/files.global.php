<?php

declare(strict_types=1);

return [
    'dependencies' => [
        'factories' => [
            'imageCacheStorage'    => new \Zestic\Flysystem\Factory\FilesystemFactory('cache'),
            'imageSourceStorage'    => new \Zestic\Flysystem\Factory\FilesystemFactory('source'),
        ],
    ],
    'files'        => [
        'cache' => [
            'flysystem' => 'imageCache',
        ],
        'source'    => [
            'flysystem' => 'imageSource',
        ],
    ],
];
