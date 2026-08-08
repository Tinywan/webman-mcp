<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

interface ConcurrencyLeaseInterface
{
    public function release(): void;
}
