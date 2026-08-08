<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\ConcurrencyLeaseInterface;

final readonly class ConcurrencyDecision
{
    public function __construct(
        public bool $allowed,
        public ?ConcurrencyLeaseInterface $lease = null,
        public ?int $retryAfterSeconds = null,
    ) {
        if ($allowed !== ($lease !== null)) {
            throw new InvalidArgumentException('Allowed concurrency decisions require exactly one lease.');
        }
        if ($retryAfterSeconds !== null && $retryAfterSeconds < 1) {
            throw new InvalidArgumentException('Retry-After must be positive.');
        }
    }
}
