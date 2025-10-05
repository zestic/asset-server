<?php

declare(strict_types=1);

namespace Application\Health;

use Laminas\Diactoros\Response\JsonResponse;
use PDO;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Throwable;

use function time;

final class HealthHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $health = [
            'status'    => 'ok',
            'timestamp' => time(),
            'checks'    => [],
        ];

        // Check database connectivity
        try {
            /** @var PDO $pdo */
            $pdo  = $this->container->get(PDO::class);
            $stmt = $pdo->query('SELECT 1');

            if ($stmt === false) {
                throw new RuntimeException('Failed to execute database query');
            }

            $result = $stmt->fetchColumn();

            if ($result === 1) {
                $health['checks']['postgres'] = [
                    'status'  => 'ok',
                    'message' => 'PostgreSQL database connection successful',
                ];
            } else {
                $health['checks']['postgres'] = [
                    'status'  => 'error',
                    'message' => 'PostgreSQL database query failed',
                ];
                $health['status']             = 'error';
            }
        } catch (Throwable $e) {
            $health['checks']['postgres'] = [
                'status'  => 'error',
                'message' => 'PostgreSQL database connection failed: ' . $e->getMessage(),
            ];
            $health['status']             = 'error';
        }

        // TODO: Add Weaviate vector database connectivity check
        // $health['checks']['weaviate'] = $this->checkWeaviate();

        // Determine HTTP status code
        $statusCode = $health['status'] === 'ok' ? 200 : 503;

        return new JsonResponse($health, $statusCode);
    }

    /**
     * Check Weaviate vector database connectivity.
     * TODO: Implement when Weaviate client is available.
     *
     * @return array<string, string>
     */
    /**
     * @phpstan-ignore-next-line method.unused
     */
    private function checkWeaviate(): array
    {
        // TODO: Implement Weaviate connectivity check
        // Example implementation:
        // try {
        //     $weaviateUrl = getenv('WEAVIATE_URL') ?: 'http://weaviate:8080';
        //     $response = $httpClient->get($weaviateUrl . '/v1/meta');
        //     return [
        //         'status' => $response->getStatusCode() === 200 ? 'ok' : 'error',
        //         'message' => 'Weaviate vector database connection successful',
        //     ];
        // } catch (Throwable $e) {
        //     return [
        //         'status' => 'error',
        //         'message' => 'Weaviate connection failed: ' . $e->getMessage(),
        //     ];
        // }

        return [
            'status'  => 'pending',
            'message' => 'Weaviate connectivity check not yet implemented',
        ];
    }
}
