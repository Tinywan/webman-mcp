<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

final readonly class PromptResult
{
    /** @param list<PromptMessage> $messages */
    public function __construct(
        public array $messages,
        public ?string $description = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = [
            'resultType' => 'complete',
            'messages' => array_map(static fn(PromptMessage $message): array => $message->toArray(), $this->messages),
        ];
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
