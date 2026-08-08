<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

use RuntimeException;
use Tinywan\Mcp\Contracts\CompletionProviderInterface;
use Tinywan\Mcp\Contracts\PromptRendererInterface;
use Tinywan\Mcp\Contracts\PromptResolverInterface;
use Tinywan\Mcp\Registry\RegisteredCompletion;
use Tinywan\Mcp\Registry\RegisteredPrompt;
use Webman\Container;

final readonly class ContainerPromptResolver implements PromptResolverInterface
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolvePrompt(RegisteredPrompt $prompt): PromptRendererInterface
    {
        return $this->requireRenderer($this->container->make($prompt->rendererClass), $prompt->rendererClass);
    }

    private function requireRenderer(mixed $renderer, string $class): PromptRendererInterface
    {
        if (!$renderer instanceof PromptRendererInterface) {
            throw new RuntimeException("Prompt renderer '{$class}' must implement PromptRendererInterface.");
        }

        return $renderer;
    }

    public function resolveCompletion(RegisteredCompletion $completion): CompletionProviderInterface
    {
        return $this->requireProvider($this->container->make($completion->providerClass), $completion->providerClass);
    }

    private function requireProvider(mixed $provider, string $class): CompletionProviderInterface
    {
        if (!$provider instanceof CompletionProviderInterface) {
            throw new RuntimeException("Completion provider '{$class}' must implement CompletionProviderInterface.");
        }

        return $provider;
    }
}
