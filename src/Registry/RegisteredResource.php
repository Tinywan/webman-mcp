<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\ResourceHandlerInterface;
use Tinywan\Mcp\Resource\ResourceDefinition;

final readonly class RegisteredResource
{
    /**
     * @param class-string<ResourceHandlerInterface> $handlerClass
     */
    public function __construct(
        public ResourceDefinition $definition,
        public string $handlerClass,
    ) {}
}
