<?php

declare(strict_types=1);

use Application\Authentication\Communication\SendMagicLinkEmail;
use Application\Authentication\Communication\SendVerificationEmail;
use Communication\Interactor\SendCommunication;
use Domain\Profile\Repository\ProfileRepositoryInterface;
use Domain\User\Interactor\UserRegistration;
use Zestic\GraphQL\AuthComponent\Communication\SendMagicLinkInterface;
use Zestic\GraphQL\AuthComponent\Communication\SendVerificationLinkInterface;
use Zestic\GraphQL\AuthComponent\Contract\UserCreatedHookInterface;
use Zestic\GraphQL\AuthComponent\Entity\MagicLinkConfig;
use Zestic\GraphQL\AuthComponent\Repository\UserRepositoryInterface;

return [
    'dependencies' => [
        'aliases'   => [
            SendMagicLinkInterface::class        => SendMagicLinkEmail::class,
            SendVerificationLinkInterface::class => SendVerificationEmail::class,
            UserCreatedHookInterface::class      => UserRegistration::class,
        ],
        'factories' => [
            SendMagicLinkEmail::class    => function ($container) {
                return new SendMagicLinkEmail(
                    $container->get(SendCommunication::class),
                    $container->get(ProfileRepositoryInterface::class),
                    $container->get(UserRepositoryInterface::class),
                    $container->get(MagicLinkConfig::class)
                );
            },
            SendVerificationEmail::class => function ($container) {
                return new SendVerificationEmail(
                    $container->get(SendCommunication::class),
                    $container->get(MagicLinkConfig::class)
                );
            },
        ],
    ],
    'auth'         => [
        'token'     => [
            'accessTokenTtl'  => (int) (getenv('AUTH_ACCESS_TOKEN_TTL') ?: 60), // Default 1 hour (in minutes)
            'loginTtl'        => (int) (getenv('AUTH_LOGIN_TTL') ?: 10), // Default 10 minutes
            'refreshTokenTtl' => (int) (getenv('AUTH_REFRESH_TOKEN_TTL') ?: 10080), // Default 1 week (in minutes)
            'registrationTtl' => (int) (getenv('AUTH_REGISTRATION_TTL') ?: 1440), // Default 24 hours (in minutes)
        ],
        'magicLink' => [
            'authCallbackPath'           => '/auth/callback',
            'magicLinkPath'              => '/auth/magic-link',
            'defaultSuccessMessage'      => 'Authentication successful',
            'registrationSuccessMessage' => 'Registration verified successfully',
        ],
    ],
];
