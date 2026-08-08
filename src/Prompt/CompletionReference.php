<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

use InvalidArgumentException;

final readonly class CompletionReference
{
    public function __construct(
        public string $type,
        public string $identifier,
    ) {
        if (!in_array($type, ['ref/prompt', 'ref/resource'], strict: true) || $identifier === '') {
            throw new InvalidArgumentException('A Completion reference must identify a Prompt or Resource.');
        }
    }

    public function key(): string
    {
        return "{$this->type}:{$this->identifier}";
    }
}
