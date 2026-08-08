<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Webman;

use Closure;
use Tinywan\Mcp\Contracts\AuditSinkInterface;
use Tinywan\Mcp\Observability\AuditEvent;

final readonly class WebmanAuditSink implements AuditSinkInterface
{
    public function __construct(
        private Closure $write,
    ) {}

    public function record(AuditEvent $event): void
    {
        ($this->write)('mcp.audit', $event->toArray());
    }
}
