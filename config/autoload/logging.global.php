<?php

declare(strict_types=1);

use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;

return [
    'dependencies' => [
        'factories' => [
            'logger.access'      => function ($container) {
                $logger = new Logger('access');

                // Add processors for additional context
                $logger->pushProcessor(new PsrLogMessageProcessor());
                $logger->pushProcessor(new WebProcessor());

                // File handler with rotation (keeps 30 days of logs)
                $fileHandler = new RotatingFileHandler(
                    '/app/logs/access.log',
                    30, // Keep 30 days
                    Logger::INFO
                );

                // Console handler for development
                if (getenv('APP_ENV') === 'development') {
                    $consoleHandler = new StreamHandler('php://stdout', Logger::DEBUG);
                    $logger->pushHandler($consoleHandler);
                }

                $logger->pushHandler($fileHandler);

                return $logger;
            },
            'logger.application' => function ($container) {
                $logger = new Logger('application');

                $logger->pushProcessor(new PsrLogMessageProcessor());
                $logger->pushProcessor(new WebProcessor());

                // Application logs
                $fileHandler = new RotatingFileHandler(
                    '/app/logs/application.log',
                    30,
                    Logger::INFO
                );

                if (getenv('APP_ENV') === 'development') {
                    $consoleHandler = new StreamHandler('php://stdout', Logger::DEBUG);
                    $logger->pushHandler($consoleHandler);
                }

                $logger->pushHandler($fileHandler);

                return $logger;
            },
        ],
    ],
];
