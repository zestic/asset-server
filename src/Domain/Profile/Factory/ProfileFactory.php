<?php

declare(strict_types=1);

namespace Domain\Profile\Factory;

use Domain\Profile\DTO\CreateProfileDTO;
use Domain\Profile\Entity\Profile;
use Domain\Profile\Repository\ProfileRepositoryInterface;

final class ProfileFactory
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository
    ) {
    }

    public function create(CreateProfileDTO $dto): Profile
    {
        $profile = new Profile()
            ->setName($dto->name);

        $this->profileRepository->save($profile);

        return $profile;
    }
}
