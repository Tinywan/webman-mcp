<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\CompletionProviderInterface;
use Tinywan\Mcp\Prompt\CompletionReference;

final readonly class RegisteredCompletion
{
    /** @param class-string<CompletionProviderInterface> $providerClass */
    public function __construct(
        public CompletionReference $reference,
        public string $providerClass,
    ) {}
}
