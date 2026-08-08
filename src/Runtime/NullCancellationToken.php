<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Runtime;

use Tinywan\Mcp\Contracts\CancellationTokenInterface;

final readonly class NullCancellationToken implements CancellationTokenInterface
{
    public function isCancelled(): bool
    {
        return false;
    }

    public function reason(): ?string
    {
        return null;
    }
}
