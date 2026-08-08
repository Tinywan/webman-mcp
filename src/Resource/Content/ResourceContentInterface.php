<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource\Content;

interface ResourceContentInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
