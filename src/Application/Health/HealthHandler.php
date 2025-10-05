<?php

declare(strict_types=1);

namespace Application\Health;

use Laminas\Diactoros\Response\JsonResponse;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function time;

final class HealthHandler implements RequestHandlerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var PDO $pdo */
            $pdo = $this->container->get(PDO::class);
            $statement = $pdo->query('SELECT 1');
            if ($statement === false) {
                throw new \RuntimeException('Failed to execute database query.');
            }
            $result = $statement->fetchColumn();
            $dbStatus = [
                'status' => $result == 1 ? 'ok' : 'fail',
                'message' => $result == 1 ? 'PostgreSQL database connection successful' : 'Unexpected database result',
            ];
        } catch (\Throwable $e) {
            $dbStatus = [
                'status' => 'fail',
                'message' => $e->getMessage(),
            ];
        }

        $status = $dbStatus['status'] === 'ok' ? 'ok' : 'fail';
        $httpStatus = $status === 'ok' ? 200 : 503;

        return new JsonResponse([
            'status' => $status,
            'timestamp' => time(),
            'checks' => [
                'postgres' => $dbStatus,
            ],
        ], $httpStatus);
    }
}
