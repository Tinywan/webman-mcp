<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Observability;

use Tinywan\Mcp\Contracts\AuditSinkInterface;

final readonly class NullAuditSink implements AuditSinkInterface
{
    public function record(AuditEvent $event): void {}
}
