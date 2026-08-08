<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Webman;

use Closure;
use Tinywan\Mcp\Contracts\TelemetryInterface;
use Tinywan\Mcp\Observability\TelemetryEvent;

final readonly class WebmanTelemetryAdapter implements TelemetryInterface
{
    /**
     * @param Closure(TelemetryEvent): void $record
     */
    public function __construct(
        private Closure $record,
    ) {}

    public function record(TelemetryEvent $event): void
    {
        ($this->record)($event);
    }
}
