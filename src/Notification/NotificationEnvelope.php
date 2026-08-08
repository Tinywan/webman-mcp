<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Notification;

use Tinywan\Mcp\Contracts\NotificationInterface;
use Tinywan\Mcp\Protocol\RequestId;

final readonly class NotificationEnvelope
{
    public function __construct(
        public NotificationInterface $notification,
        public ?RequestId $subscriptionId = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $params = $this->notification->params();
        if ($this->subscriptionId !== null) {
            $params['_meta'] = [
                'io.modelcontextprotocol/subscriptionId' => $this->subscriptionId->value(),
            ];
        }

        $envelope = ['jsonrpc' => '2.0', 'method' => $this->notification->method()];
        if ($params !== []) {
            $envelope['params'] = $params;
        }

        return $envelope;
    }
}
