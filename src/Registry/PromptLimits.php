<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use InvalidArgumentException;

final readonly class PromptLimits
{
    public function __construct(
        public int $pageSize = 100,
        public int $completionCount = 100,
    ) {
        if ($pageSize < 1 || $completionCount < 1 || $completionCount > 100) {
            throw new InvalidArgumentException('Prompt limits are invalid.');
        }
    }
}
