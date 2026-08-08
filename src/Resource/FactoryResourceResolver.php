<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

use ReflectionClass;
use RuntimeException;
use Tinywan\Mcp\Contracts\ResourceHandlerInterface;
use Tinywan\Mcp\Contracts\ResourceResolverInterface;
use Tinywan\Mcp\Registry\RegisteredResource;
use Tinywan\Mcp\Registry\RegisteredResourceTemplate;

final class FactoryResourceResolver implements ResourceResolverInterface
{
    public function resolve(RegisteredResource|RegisteredResourceTemplate $resource): ResourceHandlerInterface
    {
        $class = $resource->handlerClass;
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Resource handler '{$class}' must be instantiable.");
        }

        return $reflection->newInstance();
    }
}
