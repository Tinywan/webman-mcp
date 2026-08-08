<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Prompt;

use InvalidArgumentException;

final readonly class PromptDefinition
{
    /** @param list<PromptArgument> $arguments */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $arguments = [],
        public ?string $title = null,
    ) {
        if ($name === '') {
            throw new InvalidArgumentException('A Prompt name cannot be empty.');
        }
        $names = [];
        foreach ($arguments as $argument) {
            if (array_key_exists($argument->name, $names)) {
                throw new InvalidArgumentException("Duplicate Prompt argument '{$argument->name}'.");
            }
            $names[$argument->name] = true;
        }
    }

    public function argument(string $name): ?PromptArgument
    {
        foreach ($this->arguments as $argument) {
            if ($argument->name === $name) {
                return $argument;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = ['name' => $this->name];
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->arguments !== []) {
            $data['arguments'] = array_map(
                static fn(PromptArgument $argument): array => $argument->toArray(),
                $this->arguments,
            );
        }
        if ($this->title !== null) {
            $data['title'] = $this->title;
        }

        return $data;
    }
}
