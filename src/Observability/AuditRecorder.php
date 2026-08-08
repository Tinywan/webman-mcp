<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Observability;

use Throwable;
use Tinywan\Mcp\Contracts\AuditSinkInterface;
use Tinywan\Mcp\Contracts\TelemetryInterface;

final readonly class AuditRecorder
{
    public function __construct(
        private AuditSinkInterface $sink,
        private TelemetryInterface $telemetry,
        private string $serverId,
        private string $traceId,
        private string $method,
        private int $startedAt,
    ) {}

    public static function start(
        AuditSinkInterface $sink,
        TelemetryInterface $telemetry,
        string $serverId,
        string $traceId,
        string $method,
    ): self {
        return new self($sink, $telemetry, $serverId, $traceId, $method, (int) hrtime(as_number: true));
    }

    public function record(string $stage, string $outcome): void
    {
        $duration = max(0, (int) ((hrtime(as_number: true) - $this->startedAt) / 1_000_000));
        try {
            $this->sink->record(
                new AuditEvent($this->serverId, $this->traceId, $stage, $outcome, $duration, $this->method),
            );
            $this->telemetry->record(new TelemetryEvent('mcp.lifecycle', 'counter', 1, [
                'stage' => $stage,
                'outcome' => $outcome,
            ]));
            $this->telemetry->record(new TelemetryEvent('mcp.request.duration_ms', 'histogram', $duration, [
                'stage' => $stage,
            ]));
        } catch (Throwable) {
            try {
                $this->telemetry->record(new TelemetryEvent('mcp.observability.failure', 'counter', 1, [
                    'stage' => $stage,
                ]));
            } catch (Throwable) {
                error_log('MCP observability failure.');
            }
        }
    }
}
