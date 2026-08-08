<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\SubscriptionProviderInterface;
use Tinywan\Mcp\Subscription\SubscriptionDefinition;

final readonly class RegisteredSubscription
{
    /** @param class-string<SubscriptionProviderInterface> $providerClass */
    public function __construct(
        public SubscriptionDefinition $definition,
        public string $providerClass,
    ) {}
}
