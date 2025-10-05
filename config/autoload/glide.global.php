<?php

declare(strict_types=1);

return [
    'glide' => [
        'cache' => new \Zestic\Flysystem\Factory\FilesystemFactory('cache'),
        'source' => new \Zestic\Flysystem\Factory\FilesystemFactory('source'),
    ],
];
