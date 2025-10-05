<?php

declare(strict_types=1);

namespace Domain;

interface DTOInterface
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
