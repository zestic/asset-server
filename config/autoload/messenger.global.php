<?php

declare(strict_types=1);

use Symfony\Component\Messenger\MessageBus;

return [
    'dependencies' => [
        'factories' => [
            'messenger.command.bus'       => function ($container) {
                return new MessageBus();
            },
            'messenger.event.bus'         => function ($container) {
                return new MessageBus();
            },
            Application\CommandBus::class => function ($container) {
                return new Application\CommandBus(
                    $container->get('messenger.command.bus')
                );
            },
            Application\EventBus::class   => function ($container) {
                return new Application\EventBus(
                    $container->get('messenger.event.bus')
                );
            },
        ],
    ],
    'messenger'    => [
        'default_bus' => 'messenger.command.bus',
        'buses'       => [
            'messenger.command.bus' => [
                'middleware' => [
                    'doctrine_transaction',
                ],
            ],
            'messenger.event.bus'   => [
                'default_middleware' => 'allow_no_handlers',
                'middleware'         => [],
            ],
        ],
    ],
];
