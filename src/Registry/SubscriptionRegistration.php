<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\SubscriptionAuthorizerInterface;
use Tinywan\Mcp\Security\DenyAllSubscriptionAuthorizer;

final readonly class SubscriptionRegistration
{
    public SubscriptionAuthorizerInterface $authorizer;

    public function __construct(
        public ?RegisteredSubscription $subscription = null,
        ?SubscriptionAuthorizerInterface $authorizer = null,
    ) {
        $this->authorizer = $authorizer ?? new DenyAllSubscriptionAuthorizer();
    }
}
