<?php

declare(strict_types=1);

namespace Application\Handler;

use League\Flysystem\UnableToReadFile;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Server;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DownloadHandler implements RequestHandlerInterface
{
    public function __construct(
        private Server $server,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $filename = $request->getAttribute('filename');
        $mimeType = $request->getHeaderLine('Accept');

        if (strpos($mimeType, 'image') !== false) {
            return $this->server->getImageResponse($filename, $_GET);
        }

        // serve from Filesystem image source
        $sourcePath = $this->server->getSourcePath($filename);

        if (false === $this->server->sourceFileExists($sourcePath)) {
            throw new FileNotFoundException('Could not find the asset `' . $sourcePath . '`.');
        }

        try {
            $this->server->getSource()->read($sourcePath);
        } catch (\Exception $exception) {
            throw new UnableToReadFile('Could not read the image `' . $sourcePath . '`.', 0, $exception);
        }

        $responseFactory = $this->server->getResponseFactory();
        if ($responseFactory === null) {
            throw new \RuntimeException('No response factory configured.');
        }

        return $responseFactory->create($this->server->getSource(), $sourcePath);
    }
}
