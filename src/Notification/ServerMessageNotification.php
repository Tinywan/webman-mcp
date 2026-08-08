<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Notification;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\NotificationInterface;

final readonly class ServerMessageNotification implements NotificationInterface
{
    private const LEVELS = ['alert', 'critical', 'debug', 'emergency', 'error', 'info', 'notice', 'warning'];

    public function __construct(
        public string $level,
        public string $traceId,
        public string $outcome,
        public ?string $logger = null,
    ) {
        if (!in_array($level, self::LEVELS, strict: true)) {
            throw new InvalidArgumentException('Unsupported Server message level.');
        }
        if ($traceId === '' || $outcome === '') {
            throw new InvalidArgumentException('Server messages require a trace ID and categorical outcome.');
        }
    }

    public function method(): string
    {
        return 'notifications/message';
    }

    public function params(): array
    {
        $params = ['level' => $this->level, 'data' => ['traceId' => $this->traceId, 'outcome' => $this->outcome]];
        if ($this->logger !== null) {
            $params['logger'] = $this->logger;
        }

        return $params;
    }
}
