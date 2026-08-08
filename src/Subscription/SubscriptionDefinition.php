<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Subscription;

final readonly class SubscriptionDefinition
{
    public function __construct(
        public SubscriptionFilter $supportedNotifications,
    ) {}
}
