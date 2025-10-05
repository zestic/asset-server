<?php

declare(strict_types=1);

namespace Unit\Application\Handler;

use Application\Handler\PingHandler;

use function json_decode;

use const JSON_THROW_ON_ERROR;

use Laminas\Diactoros\Response\JsonResponse;
use PHPUnit\Framework\TestCase;

use function property_exists;

use Psr\Http\Message\ServerRequestInterface;

use function time;

final class PingHandlerTest extends TestCase
{
    public function testResponse(): void
    {
        $pingHandler = new PingHandler();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();
        $response = $pingHandler->handle($request);

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
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->addMethods([])
            ->getMock();
        $response = $pingHandler->handle($request);

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
