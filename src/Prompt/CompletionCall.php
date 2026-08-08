<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

final readonly class CompletionCall
{
    /** @param array<string, string> $context */
    public function __construct(
        public CompletionReference $reference,
        public string $argument,
        public string $value,
        public array $context = [],
    ) {}
}
