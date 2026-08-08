<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

interface ProgressReporterInterface
{
    public function report(float|int $progress, float|int|null $total = null, ?string $message = null): bool;
}
