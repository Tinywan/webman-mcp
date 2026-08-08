<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Transport;

use Tinywan\Mcp\Contracts\AuditSinkInterface;
use Tinywan\Mcp\Contracts\TelemetryInterface;
use Tinywan\Mcp\Observability\NullAuditSink;
use Tinywan\Mcp\Observability\NullTelemetry;
use Tinywan\Mcp\Subscription\SubscriptionServices;

final readonly class TransportServices
{
    public SubscriptionServices $subscriptions;

    public AuditSinkInterface $audit;

    public TelemetryInterface $telemetry;

    public function __construct(
        ?SubscriptionServices $subscriptions = null,
        ?AuditSinkInterface $audit = null,
        ?TelemetryInterface $telemetry = null,
    ) {
        $this->subscriptions = $subscriptions ?? new SubscriptionServices();
        $this->audit = $audit ?? new NullAuditSink();
        $this->telemetry = $telemetry ?? new NullTelemetry();
    }
}
