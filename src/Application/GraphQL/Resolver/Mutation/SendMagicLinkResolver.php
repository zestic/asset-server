<?php

declare(strict_types=1);

namespace Application\GraphQL\Resolver\Mutation;

use GraphQL\Middleware\Context\RequestContext;
use GraphQL\Middleware\Contract\ResolverInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Zestic\GraphQL\AuthComponent\Context\MagicLinkContext;
use Zestic\GraphQL\AuthComponent\Interactor\SendMagicLink;

final class SendMagicLinkResolver implements ResolverInterface
{
    public function __construct(
        private readonly SendMagicLink $sendMagicLink,
    ) {
    }

    /**
     * @param mixed $source
     * @param array<string, mixed> $args
     * @param RequestContext $context
     */
    public function __invoke($source, array $args, $context, ResolveInfo $info): mixed
    {
        $data             = $args['input'];
        $data['clientId'] = $context->getRequest()->getHeaderLine('X-CLIENT-ID');
        $magicLinkContext = new MagicLinkContext($data);

        return $this->sendMagicLink->send($magicLinkContext);
    }
}
