<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Notification;

use Tinywan\Mcp\Contracts\NotificationInterface;
use Tinywan\Mcp\Protocol\RequestId;

final readonly class CancelledNotification implements NotificationInterface
{
    public function __construct(
        public RequestId $requestId,
        public ?string $reason = null,
    ) {}

    public function method(): string
    {
        return 'notifications/cancelled';
    }

    public function params(): array
    {
        $params = ['requestId' => $this->requestId->value()];
        if ($this->reason !== null) {
            $params['reason'] = $this->reason;
        }

        return $params;
    }
}
