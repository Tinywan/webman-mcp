<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\NotificationInterface;
use Tinywan\Mcp\Contracts\SubscriptionProviderInterface;
use Tinywan\Mcp\Runtime\CancellationToken;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Subscription\SubscriptionCall;

final class SequenceSubscription implements SubscriptionProviderInterface
{
    /** @param list<NotificationInterface> $events */
    public function __construct(
        private readonly array $events = [],
        private readonly bool $cancelAfterFirst = false,
    ) {}

    public function notifications(SubscriptionCall $call, ExecutionContext $context): iterable
    {
        foreach (array_keys($this->events) as $index) {
            if ($index === 1 && $this->cancelAfterFirst && $context->cancellation instanceof CancellationToken) {
                $context->cancellation->cancel('test-disconnect');
            }
            yield $this->events[$index];
        }
    }
}
