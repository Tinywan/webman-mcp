<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

final readonly class ServerFeatures
{
    public function __construct(
        public ResourceRegistration $resources = new ResourceRegistration(),
        public PromptRegistration $prompts = new PromptRegistration(),
        public SubscriptionRegistration $subscriptions = new SubscriptionRegistration(),
    ) {}
}
