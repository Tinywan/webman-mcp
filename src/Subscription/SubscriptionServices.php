<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Subscription;

use Tinywan\Mcp\Contracts\CancellationCoordinatorInterface;
use Tinywan\Mcp\Contracts\SubscriptionResolverInterface;
use Tinywan\Mcp\Runtime\ProcessCancellationCoordinator;

final readonly class SubscriptionServices
{
    public SubscriptionResolverInterface $resolver;

    public CancellationCoordinatorInterface $cancellations;

    public function __construct(
        ?SubscriptionResolverInterface $resolver = null,
        ?CancellationCoordinatorInterface $cancellations = null,
    ) {
        $this->resolver = $resolver ?? new FactorySubscriptionResolver();
        $this->cancellations = $cancellations ?? new ProcessCancellationCoordinator();
    }
}
