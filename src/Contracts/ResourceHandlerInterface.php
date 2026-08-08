<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Resource\ResourceReadCall;
use Tinywan\Mcp\Resource\ResourceReadResult;
use Tinywan\Mcp\Runtime\ExecutionContext;

interface ResourceHandlerInterface
{
    public function read(ResourceReadCall $call, ExecutionContext $context): ResourceReadResult;
}
