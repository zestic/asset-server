<?php

declare(strict_types=1);

namespace Domain\Profile\Entity;

use Carbon\CarbonImmutable;

class Profile
{
    private ?string $id                 = null;
    private ?string $name               = null;
    private ?CarbonImmutable $createdAt = null;
    private ?CarbonImmutable $updatedAt = null;
    private ?CarbonImmutable $deletedAt = null;

    public function getCreatedAt(): ?CarbonImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?CarbonImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getDeletedAt(): ?CarbonImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?CarbonImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getUpdatedAt(): ?CarbonImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?CarbonImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
