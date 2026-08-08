<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Notification;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\NotificationInterface;

final readonly class ProgressNotification implements NotificationInterface
{
    public function __construct(
        #[\SensitiveParameter]
        public string|int $progressToken,
        public float|int $progress,
        public float|int|null $total = null,
        public ?string $message = null,
    ) {
        if (!is_finite((float) $progress) || $total !== null && !is_finite((float) $total)) {
            throw new InvalidArgumentException('Progress values must be finite.');
        }
    }

    public function method(): string
    {
        return 'notifications/progress';
    }

    public function params(): array
    {
        $params = ['progressToken' => $this->progressToken, 'progress' => $this->progress];
        if ($this->total !== null) {
            $params['total'] = $this->total;
        }
        if ($this->message !== null) {
            $params['message'] = $this->message;
        }

        return $params;
    }
}
