<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Runtime;

use Closure;
use InvalidArgumentException;
use Tinywan\Mcp\Contracts\CancellationTokenInterface;
use Tinywan\Mcp\Contracts\ProgressReporterInterface;
use Tinywan\Mcp\Notification\ProgressNotification;

final class CallbackProgressReporter implements ProgressReporterInterface
{
    private float|int|null $lastProgress = null;

    /** @param Closure(ProgressNotification): void $emit */
    public function __construct(
        #[\SensitiveParameter]
        private readonly string|int $progressToken,
        private readonly CancellationTokenInterface $cancellation,
        private readonly Closure $emit,
    ) {}

    public function report(float|int $progress, float|int|null $total = null, ?string $message = null): bool
    {
        if ($this->cancellation->isCancelled()) {
            return false;
        }
        if (!is_finite((float) $progress) || $total !== null && !is_finite((float) $total)) {
            throw new InvalidArgumentException('Progress values must be finite.');
        }
        if ($this->lastProgress !== null && $progress < $this->lastProgress) {
            throw new InvalidArgumentException('Progress must not decrease.');
        }
        if ($total !== null && $progress > $total) {
            throw new InvalidArgumentException('Progress must not exceed the total.');
        }

        $this->lastProgress = $progress;
        ($this->emit)(new ProgressNotification($this->progressToken, $progress, $total, $message));

        return true;
    }
}
