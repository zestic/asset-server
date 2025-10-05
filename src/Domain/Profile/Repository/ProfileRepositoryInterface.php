<?php

declare(strict_types=1);

namespace Domain\Profile\Repository;

use Domain\Profile\Entity\Profile;

interface ProfileRepositoryInterface
{
    public function findById(string $id): ?Profile;

    public function delete(string $id): void;

    public function restore(string $id): void;

    public function save(Profile $profile): void;
}
