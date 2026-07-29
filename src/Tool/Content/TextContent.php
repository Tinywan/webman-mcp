<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool\Content;

final readonly class TextContent implements ToolContentInterface
{
    public function __construct(
        public string $text,
    ) {}

    /**
     * @return array{type: string, text: string}
     */
    public function toArray(): array
    {
        return [
            'type' => 'text',
            'text' => $this->text,
        ];
    }
}
