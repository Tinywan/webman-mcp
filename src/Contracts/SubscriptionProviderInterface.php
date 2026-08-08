<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Subscription\SubscriptionCall;

interface SubscriptionProviderInterface
{
    /** @return iterable<NotificationInterface> */
    public function notifications(SubscriptionCall $call, ExecutionContext $context): iterable;
}
