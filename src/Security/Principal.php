<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

final readonly class Principal
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public string $id,
        public array $attributes = [],
        public bool $anonymous = false,
    ) {}

    public static function anonymous(): self
    {
        return new self('anonymous', [], true);
    }
}
