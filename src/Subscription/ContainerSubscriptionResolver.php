<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Subscription;

use RuntimeException;
use Tinywan\Mcp\Contracts\SubscriptionProviderInterface;
use Tinywan\Mcp\Contracts\SubscriptionResolverInterface;
use Tinywan\Mcp\Registry\RegisteredSubscription;
use Webman\Container;

final readonly class ContainerSubscriptionResolver implements SubscriptionResolverInterface
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolve(RegisteredSubscription $subscription): SubscriptionProviderInterface
    {
        return $this->requireProvider(
            $this->container->make($subscription->providerClass),
            $subscription->providerClass,
        );
    }

    private function requireProvider(mixed $provider, string $class): SubscriptionProviderInterface
    {
        if (!$provider instanceof SubscriptionProviderInterface) {
            throw new RuntimeException(
                "Subscription provider '{$class}' must implement SubscriptionProviderInterface.",
            );
        }

        return $provider;
    }
}
