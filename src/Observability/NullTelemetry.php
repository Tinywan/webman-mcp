<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Observability;

use Tinywan\Mcp\Contracts\TelemetryInterface;

final readonly class NullTelemetry implements TelemetryInterface
{
    public function record(TelemetryEvent $event): void {}
}
