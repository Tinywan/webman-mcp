<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Subscription;

use ReflectionClass;
use RuntimeException;
use Tinywan\Mcp\Contracts\SubscriptionProviderInterface;
use Tinywan\Mcp\Contracts\SubscriptionResolverInterface;
use Tinywan\Mcp\Registry\RegisteredSubscription;

final readonly class FactorySubscriptionResolver implements SubscriptionResolverInterface
{
    public function resolve(RegisteredSubscription $subscription): SubscriptionProviderInterface
    {
        $class = $subscription->providerClass;
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Subscription provider '{$class}' must be instantiable.");
        }
        return $reflection->newInstance();
    }
}
