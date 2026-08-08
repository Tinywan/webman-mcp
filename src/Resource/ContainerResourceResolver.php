<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

use RuntimeException;
use Tinywan\Mcp\Contracts\ResourceHandlerInterface;
use Tinywan\Mcp\Contracts\ResourceResolverInterface;
use Tinywan\Mcp\Registry\RegisteredResource;
use Tinywan\Mcp\Registry\RegisteredResourceTemplate;
use Webman\Container;

final readonly class ContainerResourceResolver implements ResourceResolverInterface
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolve(RegisteredResource|RegisteredResourceTemplate $resource): ResourceHandlerInterface
    {
        return $this->requireHandler($this->container->make($resource->handlerClass), $resource->handlerClass);
    }

    private function requireHandler(mixed $handler, string $class): ResourceHandlerInterface
    {
        if (!$handler instanceof ResourceHandlerInterface) {
            throw new RuntimeException("Resource handler '{$class}' must implement ResourceHandlerInterface.");
        }

        return $handler;
    }
}
