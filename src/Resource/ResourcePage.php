<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

final readonly class ResourcePage
{
    /**
     * @param list<ResourceDefinition|ResourceTemplateDefinition> $items
     */
    public function __construct(
        public array $items,
        public ?string $nextCursor = null,
    ) {}
}
