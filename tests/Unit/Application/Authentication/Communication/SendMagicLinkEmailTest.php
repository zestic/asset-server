<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Authentication\Communication;

use Application\Authentication\Communication\SendMagicLinkEmail;
use Carbon\CarbonImmutable;
use Communication\Interactor\SendCommunication;
use Domain\Profile\Entity\Profile;
use Domain\Profile\Repository\ProfileRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Zestic\GraphQL\AuthComponent\Entity\MagicLinkConfig;
use Zestic\GraphQL\AuthComponent\Entity\MagicLinkToken;
use Zestic\GraphQL\AuthComponent\Entity\MagicLinkTokenType;
use Zestic\GraphQL\AuthComponent\Entity\UserInterface;
use Zestic\GraphQL\AuthComponent\Repository\UserRepositoryInterface;

class SendMagicLinkEmailTest extends TestCase
{
    private SendMagicLinkEmail $sendMagicLinkEmail;
    private MockObject&SendCommunication $sendCommunication;
    private MockObject&ProfileRepositoryInterface $profileRepository;
    private MockObject&UserRepositoryInterface $userRepository;
    private MockObject&MagicLinkConfig $magicLinkConfig;

    protected function setUp(): void
    {
        $this->sendCommunication = $this->createMock(SendCommunication::class);
        $this->profileRepository = $this->createMock(ProfileRepositoryInterface::class);
        $this->userRepository    = $this->createMock(UserRepositoryInterface::class);
        $this->magicLinkConfig   = $this->createMock(MagicLinkConfig::class);

        $this->sendMagicLinkEmail = new SendMagicLinkEmail(
            $this->sendCommunication,
            $this->profileRepository,
            $this->userRepository,
            $this->magicLinkConfig
        );
    }

    private function createMagicLinkToken(string $userId): MagicLinkToken
    {
        return new MagicLinkToken(
            clientId: 'test-client-id',
            codeChallenge: 'test-code-challenge',
            codeChallengeMethod: 'S256',
            redirectUri: 'http://localhost:3000/callback',
            state: 'test-state',
            email: 'test@example.com',
            expiration: CarbonImmutable::now()->addMinutes(15),
            tokenType: MagicLinkTokenType::LOGIN,
            userId: $userId
        );
    }

    public function testSendSuccessfully(): void
    {
        // Arrange
        $userId          = 'user-123';
        $systemId        = 'system-456';
        $verificationUrl = 'http://localhost:8088/magic-link/verify?token=test-token';

        $magicLinkToken = $this->createMagicLinkToken($userId);

        $user = $this->createMock(UserInterface::class);
        $user->method('getSystemId')->willReturn($systemId);
        $user->method('getEmail')->willReturn('test@example.com');

        $profile = new Profile();
        $profile->setName('John Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('findUserById')
            ->with($userId)
            ->willReturn($user);

        $this->profileRepository
            ->expects($this->once())
            ->method('findById')
            ->with($systemId)
            ->willReturn($profile);

        $this->magicLinkConfig
            ->expects($this->once())
            ->method('buildRedirectUrl')
            ->with('http://localhost:8088/magic-link/verify', ['token' => $magicLinkToken->token])
            ->willReturn($verificationUrl);

        $expectedCommunication = [
            'channels'     => ['email'],
            'definitionId' => 'auth.magic-link',
            'context'      => [
                'subject' => ['name' => 'John Doe'],
                'body'    => [
                    'name' => 'John Doe',
                    'link' => $verificationUrl,
                ],
                'email'   => [
                    'name' => 'John Doe',
                    'link' => $verificationUrl,
                ],
                'sms'     => [
                    'name'  => 'John Doe',
                    'link'  => $verificationUrl,
                    'email' => 'test@example.com',
                ],
            ],
            'recipients'   => [
                [
                    'email' => 'test@example.com',
                    'name'  => 'John Doe',
                ],
            ],
        ];

        $this->sendCommunication
            ->expects($this->once())
            ->method('send')
            ->with($expectedCommunication);

        // Act
        $this->sendMagicLinkEmail->send($magicLinkToken);
    }

    public function testSendThrowsExceptionWhenUserNotFound(): void
    {
        // Arrange
        $userId = 'non-existent-user';

        $magicLinkToken = $this->createMagicLinkToken($userId);

        $this->userRepository
            ->expects($this->once())
            ->method('findUserById')
            ->with($userId)
            ->willReturn(null);

        $this->profileRepository
            ->expects($this->never())
            ->method('findById');

        $this->sendCommunication
            ->expects($this->never())
            ->method('send');

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("User not found for magic link: {$userId}");

        $this->sendMagicLinkEmail->send($magicLinkToken);
    }

    public function testSendThrowsExceptionWhenProfileNotFound(): void
    {
        // Arrange
        $userId   = 'user-123';
        $systemId = 'system-456';

        $magicLinkToken = $this->createMagicLinkToken($userId);

        $user = $this->createMock(UserInterface::class);
        $user->method('getSystemId')->willReturn($systemId);

        $this->userRepository
            ->expects($this->once())
            ->method('findUserById')
            ->with($userId)
            ->willReturn($user);

        $this->profileRepository
            ->expects($this->once())
            ->method('findById')
            ->with($systemId)
            ->willReturn(null);

        $this->sendCommunication
            ->expects($this->never())
            ->method('send');

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Profile not found for user: {$systemId}");

        $this->sendMagicLinkEmail->send($magicLinkToken);
    }

    public function testSendHandlesIntegerSystemId(): void
    {
        // Arrange
        $userId          = 'user-123';
        $systemId        = 456; // Integer system ID
        $verificationUrl = 'http://localhost:8088/magic-link/verify?token=test-token';

        $magicLinkToken = $this->createMagicLinkToken($userId);

        $user = $this->createMock(UserInterface::class);
        $user->method('getSystemId')->willReturn($systemId);
        $user->method('getEmail')->willReturn('test@example.com');

        $profile = new Profile();
        $profile->setName('Jane Doe');

        $this->userRepository
            ->expects($this->once())
            ->method('findUserById')
            ->with($userId)
            ->willReturn($user);

        // Verify that integer system ID is cast to string
        $this->profileRepository
            ->expects($this->once())
            ->method('findById')
            ->with('456') // Should be cast to string
            ->willReturn($profile);

        $this->magicLinkConfig
            ->expects($this->once())
            ->method('buildRedirectUrl')
            ->willReturn($verificationUrl);

        $this->sendCommunication
            ->expects($this->once())
            ->method('send');

        // Act
        $this->sendMagicLinkEmail->send($magicLinkToken);
    }
}
