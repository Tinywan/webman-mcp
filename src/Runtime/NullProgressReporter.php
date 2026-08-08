<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Runtime;

use Tinywan\Mcp\Contracts\ProgressReporterInterface;

final readonly class NullProgressReporter implements ProgressReporterInterface
{
    public function report(float|int $progress, float|int|null $total = null, ?string $message = null): bool
    {
        return false;
    }
}
