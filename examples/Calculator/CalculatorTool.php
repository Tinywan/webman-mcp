<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Examples\Calculator;

use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Content\TextContent;
use Tinywan\Mcp\Tool\ToolCall;
use Tinywan\Mcp\Tool\ToolDefinition;
use Tinywan\Mcp\Tool\ToolResult;

final class CalculatorTool implements ToolInterface
{
    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            'calculate',
            'Perform one arithmetic operation on two numbers.',
            [
                'type' => 'object',
                'properties' => [
                    'operation' => ['type' => 'string', 'enum' => ['add', 'subtract', 'multiply', 'divide']],
                    'left' => ['type' => 'number'],
                    'right' => ['type' => 'number'],
                ],
                'required' => ['operation', 'left', 'right'],
                'additionalProperties' => false,
            ],
            [
                'type' => 'object',
                'properties' => ['value' => ['type' => 'number']],
                'required' => ['value'],
                'additionalProperties' => false,
            ],
            'Calculator',
        );
    }

    public function call(ToolCall $call, ExecutionContext $context): ToolResult
    {
        $operation = $this->stringArgument($call, 'operation');
        $left = $this->numberArgument($call, 'left');
        $right = $this->numberArgument($call, 'right');
        if ($operation === null || $left === null || $right === null) {
            return ToolResult::error([new TextContent('Invalid calculator arguments.')]);
        }

        if ($operation === 'divide' && $right === 0.0) {
            return ToolResult::error([new TextContent('Division by zero is not allowed.')]);
        }

        $value = match ($operation) {
            'add' => $left + $right,
            'subtract' => $left - $right,
            'multiply' => $left * $right,
            'divide' => $left / $right,
            default => null,
        };
        if ($value === null) {
            return ToolResult::error([new TextContent('Unsupported calculator operation.')]);
        }

        return ToolResult::success([new TextContent((string) $value)], ['value' => $value]);
    }

    private function stringArgument(ToolCall $call, string $name): ?string
    {
        return array_key_exists($name, $call->arguments) && is_string($call->arguments[$name])
            ? $call->arguments[$name]
            : null;
    }

    private function numberArgument(ToolCall $call, string $name): ?float
    {
        if (!array_key_exists($name, $call->arguments)) {
            return null;
        }

        if (is_int($call->arguments[$name]) || is_float($call->arguments[$name])) {
            return (float) $call->arguments[$name];
        }

        return null;
    }
}
