<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use InvalidArgumentException;

final readonly class RequestId
{
    private function __construct(
        private string|int $value,
    ) {}

    public static function from(mixed $value): self
    {
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException('A request ID must be a string or integer.');
        }

        return new self($value);
    }

    public function value(): string|int
    {
        return $this->value;
    }
}
