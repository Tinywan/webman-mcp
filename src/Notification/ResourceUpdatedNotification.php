<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Notification;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\NotificationInterface;

final readonly class ResourceUpdatedNotification implements NotificationInterface
{
    public function __construct(
        public string $uri,
    ) {
        if ($uri === '') {
            throw new InvalidArgumentException('A Resource update URI cannot be empty.');
        }
    }

    public function method(): string
    {
        return 'notifications/resources/updated';
    }

    public function params(): array
    {
        return ['uri' => $this->uri];
    }
}
