<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Subscription;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\NotificationInterface;
use Tinywan\Mcp\Notification\ListChangedNotification;
use Tinywan\Mcp\Notification\ResourceUpdatedNotification;

final readonly class SubscriptionFilter
{
    /** @var list<string> */
    public array $resourceSubscriptions;

    /** @param list<string> $resourceSubscriptions */
    public function __construct(
        public bool $promptsListChanged = false,
        array $resourceSubscriptions = [],
        public bool $resourcesListChanged = false,
        public bool $toolsListChanged = false,
    ) {
        foreach ($resourceSubscriptions as $uri) {
            if ($uri === '') {
                throw new InvalidArgumentException('Resource subscription URIs cannot be empty.');
            }
        }
        $this->resourceSubscriptions = array_values(array_unique($resourceSubscriptions));
    }

    /** @return array<string, bool|list<string>> */
    public function toArray(): array
    {
        $filter = [];
        if ($this->promptsListChanged) {
            $filter['promptsListChanged'] = true;
        }
        if ($this->resourceSubscriptions !== []) {
            $filter['resourceSubscriptions'] = $this->resourceSubscriptions;
        }
        if ($this->resourcesListChanged) {
            $filter['resourcesListChanged'] = true;
        }
        if ($this->toolsListChanged) {
            $filter['toolsListChanged'] = true;
        }

        return $filter;
    }

    public function intersect(self $supported): self
    {
        return new self(
            $this->promptsListChanged && $supported->promptsListChanged,
            array_values(array_intersect($this->resourceSubscriptions, $supported->resourceSubscriptions)),
            $this->resourcesListChanged && $supported->resourcesListChanged,
            $this->toolsListChanged && $supported->toolsListChanged,
        );
    }

    public function allows(NotificationInterface $notification): bool
    {
        return match ($notification->method()) {
            ListChangedNotification::PROMPTS => $this->promptsListChanged,
            ListChangedNotification::RESOURCES => $this->resourcesListChanged,
            ListChangedNotification::TOOLS => $this->toolsListChanged,
            'notifications/resources/updated' => $notification instanceof ResourceUpdatedNotification
                && in_array($notification->uri, $this->resourceSubscriptions, strict: true),
            default => false,
        };
    }
}
