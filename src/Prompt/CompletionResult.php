<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

final readonly class CompletionResult
{
    /** @param list<string> $values */
    public function __construct(
        public array $values,
        public ?int $total = null,
        public ?bool $hasMore = null,
    ) {}
}
