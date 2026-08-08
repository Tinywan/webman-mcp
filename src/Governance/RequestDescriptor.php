<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

final readonly class RequestDescriptor
{
    public function __construct(
        public string $serverId,
        public string $principalKey,
        public string $method,
        public string $path,
        public string $traceId,
    ) {}
}
