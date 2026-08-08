<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Notification;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\NotificationInterface;

final readonly class ListChangedNotification implements NotificationInterface
{
    public const PROMPTS = 'notifications/prompts/list_changed';

    public const RESOURCES = 'notifications/resources/list_changed';

    public const TOOLS = 'notifications/tools/list_changed';

    public function __construct(
        private string $notificationMethod,
    ) {
        if (!in_array($notificationMethod, [self::PROMPTS, self::RESOURCES, self::TOOLS], strict: true)) {
            throw new InvalidArgumentException('Unsupported list changed notification method.');
        }
    }

    public function method(): string
    {
        return $this->notificationMethod;
    }

    public function params(): array
    {
        return [];
    }
}
