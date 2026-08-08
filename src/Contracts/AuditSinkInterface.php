<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Observability\AuditEvent;

interface AuditSinkInterface
{
    public function record(AuditEvent $event): void;
}
