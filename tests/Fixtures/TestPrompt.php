<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\CompletionProviderInterface;
use Tinywan\Mcp\Contracts\PromptRendererInterface;
use Tinywan\Mcp\Prompt\CompletionCall;
use Tinywan\Mcp\Prompt\CompletionResult;
use Tinywan\Mcp\Prompt\PromptCall;
use Tinywan\Mcp\Prompt\PromptMessage;
use Tinywan\Mcp\Prompt\PromptResult;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Content\TextContent;

final class TestPrompt implements PromptRendererInterface, CompletionProviderInterface
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public function render(PromptCall $call, ExecutionContext $context): PromptResult
    {
        return new PromptResult([
            new PromptMessage('user', new TextContent("{$context->principal->id}:{$call->arguments['name']}")),
        ]);
    }

    public function complete(CompletionCall $call, ExecutionContext $context): CompletionResult
    {
        return new CompletionResult(['Ada', 'Ada', 'Alan'], total: 3);
    }
}
