<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Examples\Assistant;

use Tinywan\Mcp\Contracts\CompletionProviderInterface;
use Tinywan\Mcp\Contracts\PromptRendererInterface;
use Tinywan\Mcp\Prompt\CompletionCall;
use Tinywan\Mcp\Prompt\CompletionResult;
use Tinywan\Mcp\Prompt\PromptCall;
use Tinywan\Mcp\Prompt\PromptMessage;
use Tinywan\Mcp\Prompt\PromptResult;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Content\TextContent;

final class AssistantPrompt implements PromptRendererInterface, CompletionProviderInterface
{
    public function render(PromptCall $call, ExecutionContext $context): PromptResult
    {
        return new PromptResult([
            new PromptMessage('user', new TextContent("Write a concise greeting for {$call->arguments['name']}.")),
        ], 'A personalized greeting request.');
    }

    public function complete(CompletionCall $call, ExecutionContext $context): CompletionResult
    {
        $values = array_values(array_filter(['Ada', 'Alan', 'Alice'], static fn(string $name): bool => str_starts_with(
            strtolower($name),
            strtolower($call->value),
        )));

        return new CompletionResult($values);
    }
}
