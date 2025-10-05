<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\User\Interactor;

use Domain\Profile\DTO\CreateProfileDTO;
use Domain\Profile\Entity\Profile;
use Domain\Profile\Factory\ProfileFactory;
use Domain\Profile\Repository\ProfileRepositoryInterface;
use Domain\User\Interactor\UserRegistration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zestic\GraphQL\AuthComponent\Context\RegistrationContext;
use Zestic\GraphQL\AuthComponent\Entity\UserInterface;
use Zestic\GraphQL\AuthComponent\Repository\UserRepositoryInterface;

class UserRegistrationTest extends TestCase
{
    private UserRegistration $userRegistration;
    private ProfileFactory $profileFactory;
    private MockObject&ProfileRepositoryInterface $profileRepository;
    private MockObject&UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        $this->profileRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->userRepository    = $this->createMock(UserRepositoryInterface::class);

        // Use real ProfileFactory with mocked repository
        $this->profileFactory = new ProfileFactory($this->profileRepository);

        $this->userRegistration = new UserRegistration(
            $this->profileFactory,
            $this->userRepository
        );
    }

    public function testExecuteSuccessfullyWithStringUserId(): void
    {
        // Arrange
        $userId      = 'user-123';
        $displayName = 'John Doe';
        $profileId   = 'profile-456';

        $context = $this->createRegistrationContext($displayName);

        $expectedDto = new CreateProfileDTO($displayName);

        $profile = new Profile();
        $profile->setId($profileId);

        $user = $this->createMock(UserInterface::class);

        // Expectations
        $this->profileRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Profile $profile) use ($displayName) {
                return $profile->getName() === $displayName;
            }))
            ->willReturnCallback(function (Profile $profile) use ($profileId) {
                $profile->setId($profileId);
                return $profile;
            });

        $this->userRepository
            ->expects($this->once())
            ->method('findUserById')
            ->with($userId)
            ->willReturn($user);

        $user
            ->expects($this->once())
            ->method('setSystemId')
            ->with($profileId);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($user);

        // Act
        $this->userRegistration->execute($context, $userId);
    }

    public function testExecuteSuccessfullyWithIntegerUserId(): void
    {
        // Arrange
        $userId      = 123;
        $displayName = 'Jane Doe';
        $profileId   = 'profile-789';

        $context = $this->createRegistrationContext($displayName);

        $expectedDto = new CreateProfileDTO($displayName);

        $profile = new Profile();
        $profile->setId($profileId);

        $user = $this->createMock(UserInterface::class);

        // Expectations - verify integer is cast to string
        $this->profileRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Profile $profile) use ($displayName) {
                return $profile->getName() === $displayName;
            }))
            ->willReturnCallback(function (Profile $profile) use ($profileId) {
                $profile->setId($profileId);
                return $profile;
            });

        $this->userRepository
            ->expects($this->once())
            ->method('findUserById')
            ->with('123') // Should be cast to string
            ->willReturn($user);

        $user
            ->expects($this->once())
            ->method('setSystemId')
            ->with($profileId);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($user);

        // Act
        $this->userRegistration->execute($context, $userId);
    }

    public function testExecuteThrowsExceptionWhenUserNotFound(): void
    {
        // Arrange
        $userId      = 'non-existent-user';
        $displayName = 'John Doe';

        $context = $this->createRegistrationContext($displayName);

        // Expectations
        $this->profileRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Profile $profile) use ($displayName) {
                return $profile->getName() === $displayName;
            }))
            ->willReturnCallback(function (Profile $profile) {
                $profile->setId('profile-456');
                return $profile;
            });

        $this->userRepository
            ->expects($this->once())
            ->method('findUserById')
            ->with($userId)
            ->willReturn(null); // User not found

        $this->userRepository
            ->expects($this->never())
            ->method('update');

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("User not found during registration: {$userId}");

        $this->userRegistration->execute($context, $userId);
    }

    public function testExecuteHandlesComplexAdditionalData(): void
    {
        // Arrange
        $userId      = 'user-456';
        $displayName = 'Complex User Name';
        $profileId   = 'profile-complex';

        // Create context with nested additional data structure
        $contextData = [
            'email'          => 'test@example.com',
            'additionalData' => [
                'displayName' => $displayName,
                'otherField'  => 'ignored-value',
            ],
        ];

        $context = new RegistrationContext($contextData);

        $user = $this->createMock(UserInterface::class);

        // Expectations
        $this->profileRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Profile $profile) use ($displayName) {
                return $profile->getName() === $displayName;
            }))
            ->willReturnCallback(function (Profile $profile) use ($profileId) {
                $profile->setId($profileId);
                return $profile;
            });

        $this->userRepository
            ->expects($this->once())
            ->method('findUserById')
            ->with($userId)
            ->willReturn($user);

        $user
            ->expects($this->once())
            ->method('setSystemId')
            ->with($profileId);

        $this->userRepository
            ->expects($this->once())
            ->method('update')
            ->with($user);

        // Act
        $this->userRegistration->execute($context, $userId);
    }

    private function createRegistrationContext(string $displayName): RegistrationContext
    {
        $contextData = [
            'email'          => 'test@example.com',
            'additionalData' => [
                'displayName' => $displayName,
            ],
        ];

        return new RegistrationContext($contextData);
    }
}
