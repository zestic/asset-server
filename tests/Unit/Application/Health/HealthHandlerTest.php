<?php

declare(strict_types=1);

namespace Unit\Application\Health;

use Application\Health\HealthHandler;
use Laminas\Diactoros\Response\JsonResponse;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

use function json_decode;
use function time;

use const JSON_THROW_ON_ERROR;

final class HealthHandlerTest extends TestCase
{
    private HealthHandler $handler;
    private ContainerInterface $container;
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->pdo       = $this->createMock(PDO::class);
        $this->handler   = new HealthHandler($this->container);
    }

    public function testHealthCheckWithSuccessfulDatabaseConnection(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects(self::once())
            ->method('fetchColumn')
            ->willReturn(1);

        $this->pdo->expects(self::once())
            ->method('query')
            ->with('SELECT 1')
            ->willReturn($statement);

        $this->container->expects(self::once())
            ->method('get')
            ->with(PDO::class)
            ->willReturn($this->pdo);

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->handler->handle($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('ok', $body['status']);
        self::assertIsInt($body['timestamp']);
        self::assertGreaterThan(time() - 5, $body['timestamp']);
        self::assertArrayHasKey('checks', $body);
        self::assertArrayHasKey('postgres', $body['checks']);
        self::assertEquals('ok', $body['checks']['postgres']['status']);
        self::assertEquals('PostgreSQL database connection successful', $body['checks']['postgres']['message']);
    }

    public function testHealthCheckWithFailedDatabaseConnection(): void
    {
        $this->container->expects(self::once())
            ->method('get')
            ->with(PDO::class)
            ->willThrowException(new RuntimeException('Connection failed'));

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->handler->handle($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(503, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('error', $body['status']);
        self::assertIsInt($body['timestamp']);
        self::assertArrayHasKey('checks', $body);
        self::assertArrayHasKey('postgres', $body['checks']);
        self::assertEquals('error', $body['checks']['postgres']['status']);
        self::assertStringContainsString('Connection failed', $body['checks']['postgres']['message']);
    }

    public function testHealthCheckWithDatabaseQueryFailure(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects(self::once())
            ->method('fetchColumn')
            ->willReturn(false);

        $this->pdo->expects(self::once())
            ->method('query')
            ->with('SELECT 1')
            ->willReturn($statement);

        $this->container->expects(self::once())
            ->method('get')
            ->with(PDO::class)
            ->willReturn($this->pdo);

        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->handler->handle($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertEquals(503, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertEquals('error', $body['status']);
        self::assertEquals('error', $body['checks']['postgres']['status']);
        self::assertEquals('PostgreSQL database query failed', $body['checks']['postgres']['message']);
    }
}
