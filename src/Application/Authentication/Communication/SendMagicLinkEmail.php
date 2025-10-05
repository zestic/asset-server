<?php

declare(strict_types=1);

namespace Application\Authentication\Communication;

use Communication\Interactor\SendCommunication;
use Domain\Profile\Repository\ProfileRepositoryInterface;
use RuntimeException;
use Zestic\GraphQL\AuthComponent\Communication\SendMagicLinkInterface;
use Zestic\GraphQL\AuthComponent\Entity\MagicLinkConfig;
use Zestic\GraphQL\AuthComponent\Entity\MagicLinkToken;
use Zestic\GraphQL\AuthComponent\Repository\UserRepositoryInterface;

final class SendMagicLinkEmail implements SendMagicLinkInterface
{
    public function __construct(
        private SendCommunication $sendCommunication,
        private ProfileRepositoryInterface $profileRepository,
        private UserRepositoryInterface $userRepository,
        private MagicLinkConfig $magicLinkConfig,
    ) {
    }

    public function send(MagicLinkToken $magicLinkToken): void
    {
        $user = $this->userRepository->findUserById($magicLinkToken->userId);
        if ($user === null) {
            throw new RuntimeException("User not found for magic link: {$magicLinkToken->userId}");
        }

        $profile = $this->profileRepository->findById((string) $user->getSystemId());
        if ($profile === null) {
            throw new RuntimeException("Profile not found for user: {$user->getSystemId()}");
        }

        $verificationUrl = $this->magicLinkConfig->buildRedirectUrl(
            'http://localhost:8088/magic-link/verify',
            ['token' => $magicLinkToken->token]
        );

        $communication = [
            'channels'     => [
                'email',
            ],
            'definitionId' => 'auth.magic-link',
            'context'      => [
                'subject' => [
                    'name' => $profile->getName(),
                ],
                'body'    => [
                    'name' => $profile->getName(),
                    'link' => $verificationUrl,
                ],
                'email'   => [
                    'name' => $profile->getName(),
                    'link' => $verificationUrl,
                ],
                'sms'     => [
                    'name'  => $profile->getName(),
                    'link'  => $verificationUrl,
                    'email' => $user->getEmail(),
                ],
            ],
            'recipients'   => [
                [
                    'email' => $user->getEmail(),
                    'name'  => $profile->getName(),
                ],
            ],
        ];
        $this->sendCommunication->send($communication);
    }
}
