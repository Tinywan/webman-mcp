<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

final readonly class ResourceReadCall
{
    public function __construct(
        public string $uri,
    ) {}
}
