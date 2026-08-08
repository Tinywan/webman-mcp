<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

final readonly class PromptCall
{
    /** @param array<string, string> $arguments */
    public function __construct(
        public string $name,
        public array $arguments,
    ) {}
}
