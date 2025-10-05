<?php

declare(strict_types=1);

namespace Infrastructure\DB\Hydration;

use Carbon\CarbonImmutable;

final class CarbonImmutableHydration
{
    public function dehydrate(?CarbonImmutable $carbon): ?string
    {
        return $carbon?->format('Y-m-d H:i:s.u');
    }

    public function hydrate(?string $timestamp): ?CarbonImmutable
    {
        if ($timestamp === null) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i:s.u', $timestamp);
    }
}
