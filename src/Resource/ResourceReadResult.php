<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource;

use Tinywan\Mcp\Resource\Content\ResourceContentInterface;

final readonly class ResourceReadResult
{
    /**
     * @param list<ResourceContentInterface> $contents
     */
    public function __construct(
        public array $contents,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resultType' => 'complete',
            'cacheScope' => 'private',
            'ttlMs' => 0,
            'contents' => array_map(
                static fn(ResourceContentInterface $content): array => $content->toArray(),
                $this->contents,
            ),
        ];
    }
}
