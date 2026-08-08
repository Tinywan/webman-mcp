<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Notification;

use JsonException;

final readonly class NotificationEvent
{
    /** @param array<string, mixed> $envelope */
    public function __construct(
        public array $envelope,
    ) {}

    /** @throws JsonException */
    public function json(): string
    {
        return json_encode($this->envelope, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
