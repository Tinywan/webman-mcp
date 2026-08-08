<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\PromptRendererInterface;
use Tinywan\Mcp\Prompt\PromptDefinition;

final readonly class RegisteredPrompt
{
    /** @param class-string<PromptRendererInterface> $rendererClass */
    public function __construct(
        public PromptDefinition $definition,
        public string $rendererClass,
    ) {}
}
