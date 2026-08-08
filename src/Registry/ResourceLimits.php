<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use InvalidArgumentException;

final readonly class ResourceLimits
{
    public function __construct(
        public int $pageSize = 100,
        public int $responseBytes = 2_097_152,
    ) {
        if ($pageSize < 1 || $responseBytes < 1) {
            throw new InvalidArgumentException('Resource limits must be positive.');
        }
    }
}
