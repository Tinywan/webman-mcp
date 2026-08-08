<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Subscription\SubscriptionFilter;

interface SubscriptionAuthorizerInterface
{
    public function canListen(Principal $principal, SubscriptionFilter $filter): bool;
}
