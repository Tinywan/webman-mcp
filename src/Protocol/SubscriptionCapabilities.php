<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Runtime\ExecutionContext;

final readonly class SubscriptionCapabilities
{
    /**
     * @param array<string, mixed> $capabilities
     * @return array<string, mixed>
     */
    public static function apply(array $capabilities, ServerDefinition $server, ExecutionContext $context): array
    {
        $subscription = $server->subscription();
        if (
            $subscription === null
            || !$server->features->subscriptions->authorizer->canListen(
                $context->principal,
                $subscription->definition->supportedNotifications,
            )
        ) {
            return $capabilities;
        }

        $supported = $subscription->definition->supportedNotifications;
        if (array_key_exists('tools', $capabilities)) {
            $capabilities['tools'] = ['listChanged' => $supported->toolsListChanged];
        }
        if (array_key_exists('resources', $capabilities)) {
            $capabilities['resources'] = [
                'listChanged' => $supported->resourcesListChanged,
                'subscribe' => $supported->resourceSubscriptions !== [],
            ];
        }
        if (array_key_exists('prompts', $capabilities)) {
            $capabilities['prompts'] = ['listChanged' => $supported->promptsListChanged];
        }

        return $capabilities;
    }

    private function __construct() {}
}
