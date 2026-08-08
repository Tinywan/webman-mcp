<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use Closure;
use Tinywan\Mcp\Contracts\ConcurrencyLeaseInterface;

final class ConcurrencyLease implements ConcurrencyLeaseInterface
{
    private bool $released = false;

    /** @param Closure(): void $release */
    public function __construct(
        private readonly Closure $release,
    ) {}

    public function release(): void
    {
        if ($this->released) {
            return;
        }
        $this->released = true;
        ($this->release)();
    }
}
