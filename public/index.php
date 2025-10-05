<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

// Delegate static file requests back to the PHP built-in webserver
if (PHP_SAPI === 'cli-server' && $_SERVER['SCRIPT_FILENAME'] !== __FILE__) {
    return false;
}

chdir(dirname(__DIR__));
require 'vendor/autoload.php';

// Development-only Whoops initialization with security hardening
if (getenv('APP_ENV') !== 'production') {
    $whoops  = new Run();
    $handler = new PrettyPageHandler();

    // Configure handler
    $handler->setApplicationPaths([dirname(__DIR__)]);
    $handler->addEditor('vscode', 'vscode://file/%file:%line');

    // Blacklist sensitive environment variables
    $sensitiveKeys = ['DB_PASSWORD', 'AWS_SECRET_KEY', 'JWT_SECRET'];
    foreach ($sensitiveKeys as $key) {
        $handler->blacklist('_ENV', $key);
        $handler->blacklist('_SERVER', $key);
    }

    $whoops->pushHandler($handler);
    $whoops->register();
}

// Self-called anonymous function that creates its own scope and keeps the global namespace clean
(function (): void {
    /** @var ContainerInterface $container */
    $container = require 'config/container.php';
    $app       = $container->get(Application::class);
    $factory   = $container->get(MiddlewareFactory::class);

    // Execute programmatic/declarative middleware pipeline and routing
    // configuration statements
    $pipeline = require 'config/pipeline.php';
    $routes   = require 'config/routes.php';
    $pipeline($app, $factory, $container);
    $routes($app, $factory, $container);

    $app->run();
})();
