<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\ConcurrencyLimiterInterface;

final class ProcessConcurrencyLimiter implements ConcurrencyLimiterInterface
{
    /** @var array<string, int> */
    private array $active = [];

    public function __construct(
        private readonly int $limit,
    ) {
        if ($limit < 1) {
            throw new InvalidArgumentException('Concurrency limits must be positive.');
        }
    }

    public function acquire(RequestDescriptor $request): ConcurrencyDecision
    {
        $key = $request->serverId . ':' . $request->principalKey;
        if (($this->active[$key] ?? 0) >= $this->limit) {
            return new ConcurrencyDecision(false, retryAfterSeconds: 1);
        }
        $this->active[$key] = ($this->active[$key] ?? 0) + 1;

        return new ConcurrencyDecision(true, new ConcurrencyLease(function () use ($key): void {
            $this->active[$key] = max(0, ($this->active[$key] ?? 1) - 1);
            if ($this->active[$key] === 0) {
                unset($this->active[$key]);
            }
        }));
    }

    public function active(RequestDescriptor $request): int
    {
        return $this->active[$request->serverId . ':' . $request->principalKey] ?? 0;
    }
}
