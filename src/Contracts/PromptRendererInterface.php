<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Prompt\PromptCall;
use Tinywan\Mcp\Prompt\PromptResult;
use Tinywan\Mcp\Runtime\ExecutionContext;

interface PromptRendererInterface
{
    public function render(PromptCall $call, ExecutionContext $context): PromptResult;
}
