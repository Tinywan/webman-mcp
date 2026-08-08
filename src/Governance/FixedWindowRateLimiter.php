<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\RateLimiterInterface;

final class FixedWindowRateLimiter implements RateLimiterInterface
{
    /** @var array<string, array{count: int, reset: int}> */
    private array $windows = [];

    public function __construct(
        private readonly int $limit,
        private readonly int $windowSeconds = 60,
    ) {
        if ($limit < 1 || $windowSeconds < 1) {
            throw new InvalidArgumentException('Rate limits must be positive.');
        }
    }

    public function decide(RequestDescriptor $request): RateLimitDecision
    {
        $now = time();
        $key = $request->serverId . ':' . $request->principalKey;
        $window = $this->windows[$key] ?? ['count' => 0, 'reset' => $now + $this->windowSeconds];
        if ($window['reset'] <= $now) {
            $window = ['count' => 0, 'reset' => $now + $this->windowSeconds];
        }
        ++$window['count'];
        $this->windows[$key] = $window;

        return $window['count'] <= $this->limit
            ? new RateLimitDecision(true)
            : new RateLimitDecision(false, max(1, $window['reset'] - $now));
    }
}
