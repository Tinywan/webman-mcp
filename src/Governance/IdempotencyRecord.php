<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use InvalidArgumentException;

final readonly class IdempotencyRecord
{
    /** @param array<string, string> $headers */
    public function __construct(
        public string $fingerprint,
        public int $status,
        public array $headers,
        public string $body,
        public int $expiresAt,
    ) {
        if ($fingerprint === '' || $expiresAt < 1) {
            throw new InvalidArgumentException('Idempotency records require a fingerprint and expiry.');
        }
    }
}
