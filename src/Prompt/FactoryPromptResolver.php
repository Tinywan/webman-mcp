<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

use ReflectionClass;
use RuntimeException;
use Tinywan\Mcp\Contracts\CompletionProviderInterface;
use Tinywan\Mcp\Contracts\PromptRendererInterface;
use Tinywan\Mcp\Contracts\PromptResolverInterface;
use Tinywan\Mcp\Registry\RegisteredCompletion;
use Tinywan\Mcp\Registry\RegisteredPrompt;

final class FactoryPromptResolver implements PromptResolverInterface
{
    public function resolvePrompt(RegisteredPrompt $prompt): PromptRendererInterface
    {
        $reflection = new ReflectionClass($prompt->rendererClass);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Prompt renderer '{$prompt->rendererClass}' must be instantiable.");
        }

        return $reflection->newInstance();
    }

    public function resolveCompletion(RegisteredCompletion $completion): CompletionProviderInterface
    {
        $reflection = new ReflectionClass($completion->providerClass);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Completion provider '{$completion->providerClass}' must be instantiable.");
        }

        return $reflection->newInstance();
    }
}
