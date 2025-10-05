<?php

declare(strict_types=1);

namespace Domain\Profile\DTO;

use Domain\DTOInterface;

final class CreateProfileDTO implements DTOInterface
{
    public function __construct(
        public string $name
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
