<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\ResourceHandlerInterface;
use Tinywan\Mcp\Resource\ResourceTemplateDefinition;

final readonly class RegisteredResourceTemplate
{
    /**
     * @param class-string<ResourceHandlerInterface> $handlerClass
     */
    public function __construct(
        public ResourceTemplateDefinition $definition,
        public string $handlerClass,
    ) {}
}
