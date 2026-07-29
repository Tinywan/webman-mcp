<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use RuntimeException;
use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\ToolCall;
use Tinywan\Mcp\Tool\ToolDefinition;
use Tinywan\Mcp\Tool\ToolResult;

final class ThrowingTool implements ToolInterface
{
    public function definition(): ToolDefinition
    {
        return new ToolDefinition('throwing', 'Throw a sensitive exception.', ['type' => 'object']);
    }

    public function call(ToolCall $call, ExecutionContext $context): ToolResult
    {
        throw new RuntimeException('database password=secret');
    }
}
