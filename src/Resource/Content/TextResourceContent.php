<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Resource\Content;

final readonly class TextResourceContent implements ResourceContentInterface
{
    public function __construct(
        public string $uri,
        public string $text,
        public ?string $mimeType = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['uri' => $this->uri, 'text' => $this->text];
        if ($this->mimeType !== null) {
            $data['mimeType'] = $this->mimeType;
        }

        return $data;
    }
}
