<?php

declare(strict_types=1);

return [
    // Map SHA-256 token digests to application-owned Principal definitions. Never place plaintext tokens here.
    'bearer_token_digests' => [],
    'governance' => [
        'deadline_ms' => 30_000,
        'response_bytes' => 2_097_152,
        'idempotency_ttl_ms' => 60_000,
        'idempotent_methods' => [],
        'rate_limiter' => null,
        'concurrency_limiter' => null,
        'idempotency_store' => null,
    ],
    'observability' => [
        'audit_sink' => null,
        'telemetry' => null,
    ],
];
