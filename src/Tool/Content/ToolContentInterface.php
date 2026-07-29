<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool\Content;

interface ToolContentInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
