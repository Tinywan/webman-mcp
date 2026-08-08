<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Runtime;

use Tinywan\Mcp\Contracts\CancellationTokenInterface;

final class CancellationToken implements CancellationTokenInterface
{
    private bool $cancelled = false;

    private ?string $cancellationReason = null;

    public function cancel(?string $reason = null): void
    {
        if ($this->cancelled) {
            return;
        }

        $this->cancelled = true;
        $this->cancellationReason = $reason;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }

    public function reason(): ?string
    {
        return $this->cancellationReason;
    }
}
