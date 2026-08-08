<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\AuditSinkInterface;
use Tinywan\Mcp\Observability\AuditEvent;

final class CollectingAuditSink implements AuditSinkInterface
{
    /** @var list<AuditEvent> */
    public array $events = [];

    public function record(AuditEvent $event): void
    {
        $this->events[] = $event;
    }
}
