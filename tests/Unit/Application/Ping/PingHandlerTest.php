<?php

declare(strict_types=1);

namespace Unit\Application\Ping;

use Application\Ping\PingHandler;
use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

use function json_decode;
use function property_exists;
use function time;

use const JSON_THROW_ON_ERROR;

final class PingHandlerTest extends TestCase
{
    public function testResponse(): void
    {
        $pingHandler = new PingHandler();
        $response    = $pingHandler->handle(
            $this->createMock(ServerRequestInterface::class)
        );

        $json = json_decode((string) $response->getBody(), null, 512, JSON_THROW_ON_ERROR);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertTrue(property_exists($json, 'ack') && $json->ack !== null);
        self::assertIsInt($json->ack);
        self::assertGreaterThan(0, $json->ack);

        // Verify the timestamp is recent (within last 5 seconds)
        $currentTime = time();
        self::assertLessThanOrEqual($currentTime, $json->ack);
        self::assertGreaterThan($currentTime - 5, $json->ack);
    }

    public function testResponseStructure(): void
    {
        $pingHandler = new PingHandler();
        $response    = $pingHandler->handle(
            $this->createMock(ServerRequestInterface::class)
        );

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $body = (string) $response->getBody();
        self::assertJson($body);

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);
        self::assertArrayHasKey('ack', $data);
        self::assertCount(1, $data);
    }
}
