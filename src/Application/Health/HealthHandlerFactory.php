<?php

declare(strict_types=1);

namespace Application\Health;

use Psr\Container\ContainerInterface;

final class HealthHandlerFactory
{
    public function __invoke(ContainerInterface $container): HealthHandler
    {
        return new HealthHandler($container);
    }
}
