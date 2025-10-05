<?php

declare(strict_types=1);

namespace Integration\Database;

use Application\Factory\Infrastructure\PostgresPDOFactory;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function getenv;
use function sprintf;

/**
 * Integration tests for PostgreSQL database connectivity.
 * These tests require a running PostgreSQL instance.
 *
 * @group integration
 * @group database
 */
final class PostgresConnectionTest extends TestCase
{
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testCanConnectToPostgresWithValidCredentials(): void
    {
        // Skip test if database environment variables are not set
        if (! $this->isDatabaseConfigured()) {
            $this->markTestSkipped('Database configuration not available for integration testing');
        }

        $config = [
            'db' => [
                'host'     => getenv('DB_HOST') ?: 'postgres',
                'port'     => (int) (getenv('DB_PORT') ?: 5432),
                'dbname'   => getenv('DB_NAME') ?: 'zestic_api',
                'schema'   => getenv('DB_SCHEMA') ?: 'public',
                'user'     => getenv('DB_USER') ?: 'zestic',
                'password' => getenv('DB_PASSWORD') ?: 'password1',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new PostgresPDOFactory();

        try {
            $pdo = $factory($this->container);

            // PHPStan knows this is PDO from the factory return type
            self::assertEquals(PDO::ERRMODE_EXCEPTION, $pdo->getAttribute(PDO::ATTR_ERRMODE));
            self::assertEquals(PDO::FETCH_ASSOC, $pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE));
            self::assertFalse($pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES));

            // Test a simple query to verify connection works
            $stmt = $pdo->query('SELECT version()');
            if ($stmt === false) {
                throw new RuntimeException('Failed to execute database query');
            }
            $version = $stmt->fetchColumn();

            self::assertIsString($version);
            self::assertStringContainsString('PostgreSQL', $version);
        } catch (PDOException $e) {
            $this->fail(sprintf(
                'Failed to connect to PostgreSQL database: %s. '
                . 'Ensure PostgreSQL is running and credentials are correct.',
                $e->getMessage()
            ));
        }
    }

    public function testConnectionFailsWithInvalidCredentials(): void
    {
        $config = [
            'db' => [
                'host'     => 'localhost',
                'port'     => 5432,
                'dbname'   => 'nonexistent_db',
                'schema'   => 'public',
                'user'     => 'invalid_user',
                'password' => 'invalid_password',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new PostgresPDOFactory();

        $this->expectException(PDOException::class);
        $factory($this->container);
    }

    private function isDatabaseConfigured(): bool
    {
        // Check if we're in a CI environment or have database configuration
        return getenv('CI') !== false ||
               (getenv('DB_HOST') !== false && getenv('DB_USER') !== false);
    }
}
