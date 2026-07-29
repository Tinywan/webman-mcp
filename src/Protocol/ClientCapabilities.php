<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

final readonly class ClientCapabilities
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private array $values,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
