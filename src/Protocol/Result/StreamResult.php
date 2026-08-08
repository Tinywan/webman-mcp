<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol\Result;

final readonly class StreamResult implements ProtocolDispatchResult
{
    /**
     * @param list<array<string, mixed>> $events
     */
    public function __construct(
        public array $events,
    ) {}

    public function status(): int
    {
        return 200;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-store',
            'X-Accel-Buffering' => 'no',
        ];
    }

    public function payload(): null
    {
        return null;
    }
}
