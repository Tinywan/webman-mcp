<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Content\TextContent;
use Tinywan\Mcp\Tool\ToolCall;
use Tinywan\Mcp\Tool\ToolDefinition;
use Tinywan\Mcp\Tool\ToolResult;

final class BusinessFailureTool implements ToolInterface
{
    public function definition(): ToolDefinition
    {
        return new ToolDefinition('business-failure', 'Return a business failure.', ['type' => 'object']);
    }

    public function call(ToolCall $call, ExecutionContext $context): ToolResult
    {
        return ToolResult::error([new TextContent('The operation could not be completed.')]);
    }
}
