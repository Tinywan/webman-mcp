<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Content\TextContent;
use Tinywan\Mcp\Tool\ToolCall;
use Tinywan\Mcp\Tool\ToolDefinition;
use Tinywan\Mcp\Tool\ToolResult;

final class InvalidOutputTool implements ToolInterface
{
    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            'invalid-output',
            'Return output that violates the contract.',
            ['type' => 'object'],
            [
                'type' => 'object',
                'properties' => ['value' => ['type' => 'integer']],
                'required' => ['value'],
            ],
        );
    }

    public function call(ToolCall $call, ExecutionContext $context): ToolResult
    {
        return ToolResult::success([new TextContent('invalid')], ['value' => 'not-an-integer']);
    }
}
