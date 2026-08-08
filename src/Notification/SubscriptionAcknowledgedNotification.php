<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Notification;

use Tinywan\Mcp\Contracts\NotificationInterface;
use Tinywan\Mcp\Subscription\SubscriptionFilter;

final readonly class SubscriptionAcknowledgedNotification implements NotificationInterface
{
    public function __construct(
        public SubscriptionFilter $notifications,
    ) {}

    public function method(): string
    {
        return 'notifications/subscriptions/acknowledged';
    }

    public function params(): array
    {
        return ['notifications' => $this->notifications->toArray()];
    }
}
