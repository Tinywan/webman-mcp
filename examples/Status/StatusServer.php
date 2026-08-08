<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Examples\Status;

use Tinywan\Mcp\Registry\RegisteredSubscription;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerFeatures;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\SubscriptionRegistration;
use Tinywan\Mcp\Security\AllowAllSubscriptionAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;
use Tinywan\Mcp\Subscription\SubscriptionDefinition;
use Tinywan\Mcp\Subscription\SubscriptionFilter;

final readonly class StatusServer
{
    public static function definition(): ServerDefinition
    {
        $subscription = new RegisteredSubscription(
            new SubscriptionDefinition(new SubscriptionFilter(
                resourceSubscriptions: ['status://service'],
                toolsListChanged: true,
            )),
            StatusSubscription::class,
        );

        return new ServerDefinition(
            'status',
            '/mcp/status',
            new ServerIdentity('Status MCP Server', '0.1.0'),
            [],
            new AllowAnonymousAuthenticator(),
            features: new ServerFeatures(
                subscriptions: new SubscriptionRegistration($subscription, new AllowAllSubscriptionAuthorizer()),
            ),
        );
    }

    private function __construct() {}
}
