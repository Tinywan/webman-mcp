<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Runtime;

use DateTimeImmutable;
use DateTimeZone;

final readonly class Deadline
{
    public function __construct(
        public ?DateTimeImmutable $at = null,
    ) {}

    public static function none(): self
    {
        return new self();
    }

    public static function afterMilliseconds(int $milliseconds): self
    {
        $seconds = microtime(as_float: true) + ($milliseconds / 1_000);
        $formatted = sprintf('%.6F', $seconds);
        $at = DateTimeImmutable::createFromFormat('U.u', $formatted, new DateTimeZone('UTC'));

        return new self($at === false ? new DateTimeImmutable() : $at);
    }

    public function isExpired(?DateTimeImmutable $now = null): bool
    {
        if ($this->at === null) {
            return false;
        }

        return $this->at <= ($now ?? new DateTimeImmutable());
    }
}
