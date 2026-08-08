<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\ConcurrencyLimiterInterface;
use Tinywan\Mcp\Contracts\IdempotencyStoreInterface;
use Tinywan\Mcp\Contracts\RateLimiterInterface;

final readonly class GovernanceOptions
{
    /** @var list<string> */
    public array $idempotentMethods;

    /** @param list<string> $idempotentMethods */
    public function __construct(
        public int $deadlineMs = 30_000,
        public int $responseBytes = 2_097_152,
        public int $idempotencyTtlMs = 60_000,
        array $idempotentMethods = [],
        public ?RateLimiterInterface $rateLimiter = null,
        public ?ConcurrencyLimiterInterface $concurrencyLimiter = null,
        public ?IdempotencyStoreInterface $idempotencyStore = null,
    ) {
        if ($deadlineMs < 1 || $responseBytes < 256 || $idempotencyTtlMs < 1) {
            throw new InvalidArgumentException(
                'Governance duration, response, and idempotency limits must be positive.',
            );
        }
        foreach ($idempotentMethods as $method) {
            if ($method === '') {
                throw new InvalidArgumentException('Idempotent methods cannot be empty.');
            }
        }
        $this->idempotentMethods = array_values(array_unique($idempotentMethods));
        if ($this->idempotentMethods !== [] && $idempotencyStore === null) {
            throw new InvalidArgumentException('Configured idempotent methods require an idempotency store.');
        }
    }

    public function isIdempotent(string $method): bool
    {
        return in_array($method, $this->idempotentMethods, strict: true);
    }
}
