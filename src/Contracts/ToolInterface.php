<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\ToolCall;
use Tinywan\Mcp\Tool\ToolDefinition;
use Tinywan\Mcp\Tool\ToolResult;

interface ToolInterface
{
    public function definition(): ToolDefinition;

    public function call(ToolCall $call, ExecutionContext $context): ToolResult;
}
