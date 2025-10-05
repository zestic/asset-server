<?php

declare(strict_types=1);

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

/**
 * FastRoute route configuration
 *
 * @see https://github.com/nikic/FastRoute
 */

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->post('/graphql', [
        \GraphQL\Upload\UploadMiddleware::class,
        \GraphQL\Middleware\GraphQLMiddleware::class,
    ], 'graphql');
    $app->get('/ping', \Application\Ping\PingHandler::class, 'ping');
    $app->get('/health', \Application\Health\HealthHandler::class, 'health');
    $app->get('/test-error', function() {
        throw new \RuntimeException('Test error for Whoops');
    }, 'test.error');

    // OAuth2 Authorization endpoint
    $app->route('/oauth/authorize', [
        \Zestic\GraphQL\AuthComponent\Application\Handler\AuthorizationRequestHandler::class,
    ], ['GET', 'POST'], 'oauth.authorize');

    // OAuth2 Token endpoint
    $app->post('/oauth/token', [
        \Zestic\GraphQL\AuthComponent\Application\Handler\TokenRequestHandler::class,
    ], 'oauth.token');

    // Magic Link Verification endpoint
    $app->get('/magic-link/verify', [
        \Zestic\GraphQL\AuthComponent\Application\Handler\MagicLinkVerificationHandler::class,
    ], 'magic-link.verify');
};
