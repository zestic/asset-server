<?php

declare(strict_types=1);

namespace Application;

abstract class AbstractDTO
{
    /** @var array<string, mixed> */
    protected array $data = [];

    /** @return array<string, mixed> */
    public function getData(): array
    {
        return $this->data;
    }

    /** @param array<string, mixed> $data */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }
}
