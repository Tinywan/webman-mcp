<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Registry\RegisteredCompletion;
use Tinywan\Mcp\Registry\RegisteredPrompt;

interface PromptResolverInterface
{
    public function resolvePrompt(RegisteredPrompt $prompt): PromptRendererInterface;

    public function resolveCompletion(RegisteredCompletion $completion): CompletionProviderInterface;
}
