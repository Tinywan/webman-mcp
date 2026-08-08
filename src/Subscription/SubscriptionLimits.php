<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Subscription;

use InvalidArgumentException;

final readonly class SubscriptionLimits
{
    public function __construct(
        public int $eventCount = 100,
        public int $eventBytes = 65_536,
        public int $totalBytes = 1_048_576,
        public int $lifetimeMs = 30_000,
    ) {
        if ($eventCount < 1 || $eventBytes < 1 || $totalBytes < $eventBytes || $lifetimeMs < 1) {
            throw new InvalidArgumentException('Subscription limits must be positive and internally consistent.');
        }
    }
}
