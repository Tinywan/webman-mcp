<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

interface CancellationTokenInterface
{
    public function isCancelled(): bool;

    public function reason(): ?string;
}
