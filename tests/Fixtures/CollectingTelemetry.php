<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\TelemetryInterface;
use Tinywan\Mcp\Observability\TelemetryEvent;

final class CollectingTelemetry implements TelemetryInterface
{
    /** @var list<TelemetryEvent> */
    public array $events = [];

    public function record(TelemetryEvent $event): void
    {
        $this->events[] = $event;
    }
}
