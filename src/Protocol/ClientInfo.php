<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

final readonly class ClientInfo
{
    public function __construct(
        public string $name,
        public string $version,
        public ?string $title = null,
    ) {}

    /**
     * @return array{name: string, version: string, title?: string}
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'version' => $this->version,
        ];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        return $data;
    }
}
