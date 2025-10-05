<?php

declare(strict_types=1);

namespace Application\GraphQL\Resolver\Mutation;

use GraphQL\Middleware\Context\RequestContext;
use GraphQL\Middleware\Contract\ResolverInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Zestic\GraphQL\AuthComponent\Context\RegistrationContext;
use Zestic\GraphQL\AuthComponent\Interactor\RegisterUser;

class RegisterResolver implements ResolverInterface
{
    public function __construct(
        private readonly RegisterUser $registerUser,
    ) {
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param RequestContext $context
     */
    public function __invoke($source, array $args, $context, ResolveInfo $info): mixed
    {
        $data                = $args['input'];
        $data['clientId']    = $context->getRequest()->getHeaderLine('X-CLIENT-ID');
        $data['displayName'] = $data['additionalData']['displayName'];
        $registrationContext = new RegistrationContext($data);

        return $this->registerUser->register($registrationContext);
    }
}
