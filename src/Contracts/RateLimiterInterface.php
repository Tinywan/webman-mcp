<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Governance\RateLimitDecision;
use Tinywan\Mcp\Governance\RequestDescriptor;

interface RateLimiterInterface
{
    public function decide(RequestDescriptor $request): RateLimitDecision;
}
