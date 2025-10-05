<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\DB\Hydration;

use Carbon\CarbonImmutable;
use Infrastructure\DB\Hydration\CarbonImmutableHydration;
use PHPUnit\Framework\TestCase;

final class CarbonImmutableHydrationTest extends TestCase
{
    private CarbonImmutableHydration $hydration;

    protected function setUp(): void
    {
        $this->hydration = new CarbonImmutableHydration();
    }

    public function testDehydrateWithCarbonImmutable(): void
    {
        $carbon = CarbonImmutable::parse('2025-06-26 02:19:18.092167');

        $result = $this->hydration->dehydrate($carbon);

        self::assertEquals('2025-06-26 02:19:18.092167', $result);
    }

    public function testDehydrateWithNull(): void
    {
        $result = $this->hydration->dehydrate(null);

        self::assertNull($result);
    }

    public function testHydrateWithTimestampString(): void
    {
        $timestamp = '2025-06-26 02:19:18.092167';

        $result = $this->hydration->hydrate($timestamp);

        self::assertInstanceOf(CarbonImmutable::class, $result);
        self::assertEquals('2025-06-26 02:19:18.092167', $result->format('Y-m-d H:i:s.u'));
    }

    public function testHydrateWithNull(): void
    {
        $result = $this->hydration->hydrate(null);

        self::assertNull($result);
    }

    public function testHydrateWithTimestampWithoutMicroseconds(): void
    {
        $timestamp = '2025-06-26 02:19:18.000000';

        $result = $this->hydration->hydrate($timestamp);

        self::assertInstanceOf(CarbonImmutable::class, $result);
        self::assertEquals('2025-06-26 02:19:18.000000', $result->format('Y-m-d H:i:s.u'));
    }

    public function testRoundTripConversion(): void
    {
        $originalCarbon = CarbonImmutable::parse('2025-12-31 23:59:59.123456');

        // Dehydrate to string
        $dehydrated = $this->hydration->dehydrate($originalCarbon);

        // Hydrate back to CarbonImmutable
        $rehydrated = $this->hydration->hydrate($dehydrated);

        self::assertInstanceOf(CarbonImmutable::class, $rehydrated);
        self::assertTrue($originalCarbon->equalTo($rehydrated));
        self::assertEquals($originalCarbon->format('Y-m-d H:i:s.u'), $rehydrated->format('Y-m-d H:i:s.u'));
    }

    public function testRoundTripConversionWithNull(): void
    {
        // Dehydrate null
        $dehydrated = $this->hydration->dehydrate(null);

        // Hydrate null back
        $rehydrated = $this->hydration->hydrate($dehydrated);

        self::assertNull($dehydrated);
        self::assertNull($rehydrated);
    }

    public function testHydrateWithDifferentTimezones(): void
    {
        // PostgreSQL typically stores timestamps in UTC
        $timestamp = '2025-06-26 02:19:18.092167';

        $result = $this->hydration->hydrate($timestamp);

        self::assertInstanceOf(CarbonImmutable::class, $result);
        self::assertEquals('2025-06-26 02:19:18.092167', $result->format('Y-m-d H:i:s.u'));
        // CarbonImmutable should preserve the original timezone context
        self::assertEquals('UTC', $result->getTimezone()->getName());
    }

    public function testDehydratePreservesFormat(): void
    {
        $carbon = CarbonImmutable::createFromFormat('Y-m-d H:i:s.u', '2025-01-01 00:00:00.000001');

        $result = $this->hydration->dehydrate($carbon);

        self::assertEquals('2025-01-01 00:00:00.000001', $result);
    }
}
