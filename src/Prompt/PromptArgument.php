<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

use InvalidArgumentException;

final readonly class PromptArgument
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public bool $required = false,
        public ?string $title = null,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('A Prompt argument name cannot be empty.');
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = ['name' => $this->name];
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->required) {
            $data['required'] = true;
        }
        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        return $data;
    }
}
