<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Content\TextContent;
use Tinywan\Mcp\Tool\ToolCall;
use Tinywan\Mcp\Tool\ToolDefinition;
use Tinywan\Mcp\Tool\ToolResult;

final class EchoTool implements ToolInterface
{
    public static int $instances = 0;

    private int $instance;

    public function __construct()
    {
        self::$instances++;
        $this->instance = self::$instances;
    }

    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            'echo',
            'Echo a message with request identity.',
            [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'x-mcp-header' => 'Message',
                    ],
                ],
                'required' => ['message'],
                'additionalProperties' => false,
            ],
            [
                'type' => 'object',
                'properties' => [
                    'message' => ['type' => 'string'],
                    'principal' => ['type' => 'string'],
                    'instance' => ['type' => 'integer'],
                ],
                'required' => ['message', 'principal', 'instance'],
                'additionalProperties' => false,
            ],
        );
    }

    public function call(ToolCall $call, ExecutionContext $context): ToolResult
    {
        if (!is_string($call->arguments['message'] ?? null)) {
            return ToolResult::error([new TextContent('Invalid message.')]);
        }
        $message = $call->arguments['message'];

        $structured = [
            'message' => $message,
            'principal' => $context->principal->id,
            'instance' => $this->instance,
        ];

        return ToolResult::success([new TextContent($message)], $structured);
    }
}
