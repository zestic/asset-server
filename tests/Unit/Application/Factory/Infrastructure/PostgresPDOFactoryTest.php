<?php

declare(strict_types=1);

namespace Unit\Application\Factory\Infrastructure;

use Application\Factory\Infrastructure\PostgresPDOFactory;
use PDOException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

final class PostgresPDOFactoryTest extends TestCase
{
    private PostgresPDOFactory $factory;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->factory   = new PostgresPDOFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testFactoryWithValidConfigAttemptsConnection(): void
    {
        $config = [
            'db' => [
                'host'     => 'localhost',
                'port'     => 5432,
                'dbname'   => 'test_db',
                'schema'   => 'public',
                'user'     => 'test_user',
                'password' => 'test_password',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        // We expect this to throw a PDOException due to connection failure,
        // but not a RuntimeException due to missing configuration
        $this->expectException(PDOException::class);

        ($this->factory)($this->container);
    }

    public function testFactoryThrowsExceptionWhenDbConfigNotFound(): void
    {
        $config = [];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database configuration not found');

        ($this->factory)($this->container);
    }

    public function testFactoryThrowsExceptionWhenHostNotConfigured(): void
    {
        $config = [
            'db' => [
                'port'     => 5432,
                'dbname'   => 'test_db',
                'schema'   => 'public',
                'user'     => 'test_user',
                'password' => 'test_password',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database host not configured');

        ($this->factory)($this->container);
    }

    public function testFactoryThrowsExceptionWhenPortNotConfigured(): void
    {
        $config = [
            'db' => [
                'host'     => 'localhost',
                'dbname'   => 'test_db',
                'schema'   => 'public',
                'user'     => 'test_user',
                'password' => 'test_password',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database port not configured');

        ($this->factory)($this->container);
    }

    public function testFactoryThrowsExceptionWhenDbnameNotConfigured(): void
    {
        $config = [
            'db' => [
                'host'     => 'localhost',
                'port'     => 5432,
                'schema'   => 'public',
                'user'     => 'test_user',
                'password' => 'test_password',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database name not configured');

        ($this->factory)($this->container);
    }

    public function testFactoryThrowsExceptionWhenSchemaNotConfigured(): void
    {
        $config = [
            'db' => [
                'host'     => 'localhost',
                'port'     => 5432,
                'dbname'   => 'test_db',
                'user'     => 'test_user',
                'password' => 'test_password',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database schema not configured');

        ($this->factory)($this->container);
    }

    public function testFactoryThrowsExceptionWhenUserNotConfigured(): void
    {
        $config = [
            'db' => [
                'host'     => 'localhost',
                'port'     => 5432,
                'dbname'   => 'test_db',
                'schema'   => 'public',
                'password' => 'test_password',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database user not configured');

        ($this->factory)($this->container);
    }

    public function testFactoryThrowsExceptionWhenPasswordNotConfigured(): void
    {
        $config = [
            'db' => [
                'host'   => 'localhost',
                'port'   => 5432,
                'dbname' => 'test_db',
                'schema' => 'public',
                'user'   => 'test_user',
            ],
        ];

        $this->container
            ->expects(self::once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Database password not configured');

        ($this->factory)($this->container);
    }
}
