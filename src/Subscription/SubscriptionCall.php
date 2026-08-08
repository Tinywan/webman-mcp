<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Subscription;

use Tinywan\Mcp\Protocol\RequestId;

final readonly class SubscriptionCall
{
    public function __construct(
        public RequestId $requestId,
        public SubscriptionFilter $notifications,
    ) {}
}
