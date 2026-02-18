<?php

declare(strict_types=1);

namespace Application\Factory;

use Application\Handler\DownloadHandler;
use League\Glide\Server;
use Psr\Container\ContainerInterface;

final class DownloadHandlerFactory
{
    public function __invoke(ContainerInterface $container): DownloadHandler
    {
        return new DownloadHandler(
            $container->get(Server::class),
        );
    }
}
