<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol;

final readonly class ProtocolRequest
{
    /**
     * @param array<string, mixed> $params
     */
    public function __construct(
        public ?RequestId $id,
        public string $method,
        public array $params,
        public string $protocolVersion,
        public ClientCapabilities $clientCapabilities,
        public ?ClientInfo $clientInfo = null,
    ) {}

    public function isNotification(): bool
    {
        return $this->id === null;
    }
}
