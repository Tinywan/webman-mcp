<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Observability\TelemetryEvent;

interface TelemetryInterface
{
    public function record(TelemetryEvent $event): void;
}
