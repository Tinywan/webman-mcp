<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Protocol\ProtocolRequest;
use Tinywan\Mcp\Protocol\Result\ProtocolDispatchResult;
use Tinywan\Mcp\Runtime\ExecutionContext;

interface ProtocolDriverInterface
{
    public function dispatch(ProtocolRequest $request, ExecutionContext $context): ProtocolDispatchResult;
}
