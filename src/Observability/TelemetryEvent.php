<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Observability;

use InvalidArgumentException;

final readonly class TelemetryEvent
{
    /** @param array<string, string> $tags */
    public function __construct(
        public string $name,
        public string $type,
        public float $value,
        public array $tags = [],
    ) {
        if ($name === '' || !in_array($type, ['counter', 'histogram'], strict: true) || !is_finite($value)) {
            throw new InvalidArgumentException('Invalid telemetry event.');
        }
    }
}
