<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use InvalidArgumentException;
use Tinywan\Mcp\Governance\GovernanceOptions;
use Tinywan\Mcp\Subscription\SubscriptionLimits;

final readonly class ServerOptions
{
    public function __construct(
        public OriginPolicy $originPolicy = new OriginPolicy(),
        public ?string $instructions = null,
        public int $bodyLimit = 2_097_152,
        public ResourceLimits $resources = new ResourceLimits(),
        public PromptLimits $prompts = new PromptLimits(),
        public SubscriptionLimits $subscriptionLimits = new SubscriptionLimits(),
        public GovernanceOptions $governance = new GovernanceOptions(),
    ) {
        if ($bodyLimit < 1) {
            throw new InvalidArgumentException('The Server body limit must be positive.');
        }
    }
}
