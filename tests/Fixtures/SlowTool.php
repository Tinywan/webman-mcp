<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Content\TextContent;
use Tinywan\Mcp\Tool\ToolCall;
use Tinywan\Mcp\Tool\ToolDefinition;
use Tinywan\Mcp\Tool\ToolResult;

final readonly class SlowTool implements ToolInterface
{
    public function definition(): ToolDefinition
    {
        return new ToolDefinition('slow', 'Complete after a short delay.', [
            'type' => 'object',
            'additionalProperties' => false,
        ]);
    }

    public function call(ToolCall $call, ExecutionContext $context): ToolResult
    {
        usleep(10_000);

        return ToolResult::success([new TextContent('complete')]);
    }
}
