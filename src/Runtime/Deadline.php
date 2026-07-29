<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Runtime;

use DateTimeImmutable;

final readonly class Deadline
{
    public function __construct(
        public ?DateTimeImmutable $at = null,
    ) {}

    public static function none(): self
    {
        return new self();
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        if ($this->at === null) {
            return false;
        }

        return $this->at <= ($now ?? new DateTimeImmutable());
    }
}
