<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

use Tinywan\Mcp\Contracts\SubscriptionAuthorizerInterface;
use Tinywan\Mcp\Subscription\SubscriptionFilter;

final readonly class AllowAllSubscriptionAuthorizer implements SubscriptionAuthorizerInterface
{
    public function canListen(Principal $principal, SubscriptionFilter $filter): bool
    {
        return true;
    }
}
