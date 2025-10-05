<?php

declare(strict_types=1);

namespace Application\Authentication\Communication;

use Communication\Interactor\SendCommunication;
use Zestic\GraphQL\AuthComponent\Communication\SendVerificationLinkInterface;
use Zestic\GraphQL\AuthComponent\Context\RegistrationContext;
use Zestic\GraphQL\AuthComponent\Entity\MagicLinkConfig;
use Zestic\GraphQL\AuthComponent\Entity\MagicLinkToken;

final class SendVerificationEmail implements SendVerificationLinkInterface
{
    public function __construct(
        private SendCommunication $sendCommunication,
        private MagicLinkConfig $magicLinkConfig,
    ) {
    }

    public function send(RegistrationContext $context, MagicLinkToken $token): void
    {
        // Generate the verification URL pointing to the /magic-link/verify endpoint
        $verificationUrl = $this->magicLinkConfig->buildRedirectUrl(
            'http://localhost:8088/magic-link/verify',
            ['token' => $token->token]
        );

        $communication = [
            'channels'     => [
                'email',
            ],
            'definitionId' => 'auth.email-verification',
            'context'      => [
                'subject' => [
                    'name' => $context->get('displayName'),
                ],
                'body'    => [
                    'name' => $context->get('displayName'),
                    'link' => $verificationUrl,
                ],
            ],
            'recipients'   => [
                [
                    'email' => $context->get('email'),
                    'name'  => $context->get('displayName'),
                ],
            ],
        ];
        $this->sendCommunication->send($communication);
    }
}
