<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Governance\ConcurrencyDecision;
use Tinywan\Mcp\Governance\RequestDescriptor;

interface ConcurrencyLimiterInterface
{
    public function acquire(RequestDescriptor $request): ConcurrencyDecision;
}
