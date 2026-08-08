<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Prompt\CompletionCall;
use Tinywan\Mcp\Prompt\CompletionResult;
use Tinywan\Mcp\Runtime\ExecutionContext;

interface CompletionProviderInterface
{
    public function complete(CompletionCall $call, ExecutionContext $context): CompletionResult;
}
