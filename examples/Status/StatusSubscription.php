<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Examples\Status;

use Tinywan\Mcp\Contracts\NotificationInterface;
use Tinywan\Mcp\Contracts\SubscriptionProviderInterface;
use Tinywan\Mcp\Notification\ListChangedNotification;
use Tinywan\Mcp\Notification\ResourceUpdatedNotification;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Subscription\SubscriptionCall;

final readonly class StatusSubscription implements SubscriptionProviderInterface
{
    /** @return iterable<NotificationInterface> */
    public function notifications(SubscriptionCall $call, ExecutionContext $context): iterable
    {
        yield new ListChangedNotification(ListChangedNotification::TOOLS);
        yield new ResourceUpdatedNotification('status://service');
    }
}
