<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Observability;

use InvalidArgumentException;

final readonly class AuditEvent
{
    private const STAGES = [
        'authentication',
        'authorization',
        'dispatch',
        'handler',
        'response',
        'cancellation',
        'stream',
    ];

    public function __construct(
        public string $serverId,
        public string $traceId,
        public string $stage,
        public string $outcome,
        public int $durationMs,
        public ?string $method = null,
    ) {
        if (!in_array($stage, self::STAGES, strict: true) || $outcome === '' || $durationMs < 0) {
            throw new InvalidArgumentException('Invalid audit event.');
        }
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        $event = [
            'serverId' => $this->serverId,
            'traceId' => $this->traceId,
            'stage' => $this->stage,
            'outcome' => $this->outcome,
            'durationMs' => $this->durationMs,
        ];
        if ($this->method !== null) {
            $event['method'] = $this->method;
        }

        return $event;
    }
}
