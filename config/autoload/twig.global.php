<?php

declare(strict_types=1);

use Application\Twig\Factory\TwigEnvironmentFactory;
use Twig\Environment;

return [
    'dependencies' => [
        'factories' => [
            Environment::class => TwigEnvironmentFactory::class,
        ],
    ],
    'templates'    => [
        'extension' => 'html.twig',
        'paths'     => [
            realpath(getcwd() . '/resources/templates/print'),
        ],
    ],
    'twig'         => [
        'autoescape' => 'html', // Auto-escaping strategy [html|js|css|url|false]
        'cache_dir'  => realpath(getcwd() . '/data/cache/twig'),
        'assets_url' => 'https://img.xddx.nl',
        //        'assets_version' => 'base version for assets',
        'extensions'      => [
            // extension service names or instances
        ],
        'globals'         => [
            // Global variables passed to twig templates
            'ga_tracking' => 'UA-XXXXX-X',
        ],
        'optimizations'   => -1, // -1: Enable all (default), 0: disable optimizations
        'runtime_loaders' => [
            // runtime loader names or instances
        ],
        // 'timezone' => getenv('TIMEZONE'),
        'auto_reload' => true, // Recompile the template whenever the source code changes
    ],
];
