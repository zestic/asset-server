<?php

declare(strict_types=1);

namespace Application\Factory;

use ConfigValue\GatherConfigValues;
use League\Glide\Server;
use League\Glide\ServerFactory;
use Psr\Container\ContainerInterface;
use Zestic\Flysystem\Factory\FilesystemFactory;

final class GlideServerFactory
{
    public function __invoke(ContainerInterface $container): Server
    {
        $config = (new GatherConfigValues())($container, 'glide');
        $config['cache'] = (new FilesystemFactory('cache'))($container);
        $config['source'] = (new FilesystemFactory('source'))($container);
        $config['response'] = new ResponseFactory();

        return (new ServerFactory($config))->getServer();
    }
}
