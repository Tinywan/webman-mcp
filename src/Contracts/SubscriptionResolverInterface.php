<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Registry\RegisteredSubscription;

interface SubscriptionResolverInterface
{
    public function resolve(RegisteredSubscription $subscription): SubscriptionProviderInterface;
}
