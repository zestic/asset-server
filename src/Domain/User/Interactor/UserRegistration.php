<?php

declare(strict_types=1);

namespace Domain\User\Interactor;

use Domain\Profile\DTO\CreateProfileDTO;
use Domain\Profile\Factory\ProfileFactory;
use RuntimeException;
use Zestic\GraphQL\AuthComponent\Context\RegistrationContext;
use Zestic\GraphQL\AuthComponent\Contract\UserCreatedHookInterface;
use Zestic\GraphQL\AuthComponent\Repository\UserRepositoryInterface;

final class UserRegistration implements UserCreatedHookInterface
{
    public function __construct(
        private readonly ProfileFactory $profileFactory,
        // private readonly WorkspaceFactory $workspaceFactory,
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(RegistrationContext $context, int|string $userId): void
    {
        $dto     = $this->getProfileDTO($context);
        $profile = $this->profileFactory->create($dto);

        $user = $this->userRepository->findUserById((string) $userId);
        if ($user === null) {
            throw new RuntimeException("User not found during registration: {$userId}");
        }

        $user->setSystemId($profile->getId());
        $this->userRepository->update($user);
    }

    private function getProfileDTO(RegistrationContext $context): CreateProfileDTO
    {
        $additionalData = $context->get('additionalData');

        return new CreateProfileDTO($additionalData['displayName']);
    }
}
