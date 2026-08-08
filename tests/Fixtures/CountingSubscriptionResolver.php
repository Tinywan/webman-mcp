<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\SubscriptionProviderInterface;
use Tinywan\Mcp\Contracts\SubscriptionResolverInterface;
use Tinywan\Mcp\Registry\RegisteredSubscription;

final class CountingSubscriptionResolver implements SubscriptionResolverInterface
{
    public int $resolutions = 0;

    public function __construct(
        private readonly SubscriptionProviderInterface $provider,
    ) {}

    public function resolve(RegisteredSubscription $subscription): SubscriptionProviderInterface
    {
        ++$this->resolutions;

        return $this->provider;
    }
}
