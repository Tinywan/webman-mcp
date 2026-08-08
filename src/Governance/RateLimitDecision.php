<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use InvalidArgumentException;

final readonly class RateLimitDecision
{
    public function __construct(
        public bool $allowed,
        public ?int $retryAfterSeconds = null,
    ) {
        if ($retryAfterSeconds !== null && $retryAfterSeconds < 1) {
            throw new InvalidArgumentException('Retry-After must be positive.');
        }
    }
}
