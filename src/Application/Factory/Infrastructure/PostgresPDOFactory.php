<?php

declare(strict_types=1);

namespace Application\Factory\Infrastructure;

use PDO;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function sprintf;

final class PostgresPDOFactory
{
    public function __invoke(ContainerInterface $container): PDO
    {
        $config   = $container->get('config');
        $dbConfig = $config['db'] ?? throw new RuntimeException('Database configuration not found');

        $host     = $dbConfig['host'] ?? throw new RuntimeException('Database host not configured');
        $port     = $dbConfig['port'] ?? throw new RuntimeException('Database port not configured');
        $dbname   = $dbConfig['dbname'] ?? throw new RuntimeException('Database name not configured');
        $schema   = $dbConfig['schema'] ?? throw new RuntimeException('Database schema not configured');
        $user     = $dbConfig['user'] ?? throw new RuntimeException('Database user not configured');
        $password = $dbConfig['password'] ?? throw new RuntimeException('Database password not configured');

        // Build DSN with schema
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;options=--search_path=%s',
            $host,
            $port,
            $dbname,
            $schema
        );

        // Create PDO instance with error mode set to exceptions
        return new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
}
