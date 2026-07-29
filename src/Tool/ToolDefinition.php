<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool;

use InvalidArgumentException;

final readonly class ToolDefinition
{
    /**
     * @param array<string, mixed> $inputSchema
     * @param null|array<string, mixed> $outputSchema
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $inputSchema,
        public ?array $outputSchema = null,
        public ?string $title = null,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('A Tool name cannot be empty.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'inputSchema' => $this->inputSchema,
        ];

        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        if ($this->outputSchema !== null) {
            $data['outputSchema'] = $this->outputSchema;
        }

        return $data;
    }
}
