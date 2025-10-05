<?php

declare(strict_types=1);

use Laminas\ServiceManager\AbstractFactory\ReflectionBasedAbstractFactory;

return [
    'dependencies' => [
        'abstract_factories' => [
            ReflectionBasedAbstractFactory::class,
        ],
        'aliases'            => [
            Domain\Profile\Repository\ProfileRepositoryInterface::class
                => Infrastructure\Profile\ProfileRepository::class,
            Zestic\GraphQL\AuthComponent\Contract\UserCreatedHookInterface::class
                => Domain\User\Interactor\UserRegistration::class,
        ],
        'invokables'         => [
            GraphQL\Middleware\Context\RequestContext::class,
        ],
        'factories'          => [
            Application\Health\HealthHandler::class => Application\Health\HealthHandlerFactory::class,
        ],
    ],
];
