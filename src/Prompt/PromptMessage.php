<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

use InvalidArgumentException;
use Tinywan\Mcp\Tool\Content\ToolContentInterface;

final readonly class PromptMessage
{
    public function __construct(
        public string $role,
        public ToolContentInterface $content,
    ) {
        if (!in_array($role, ['user', 'assistant'], strict: true)) {
            throw new InvalidArgumentException('A Prompt message role must be user or assistant.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['role' => $this->role, 'content' => $this->content->toArray()];
    }
}
