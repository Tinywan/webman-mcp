<?php

declare(strict_types=1);

use Tinywan\Mcp\Version;

it('exposes the SDK and protocol versions', function (): void {
    expect(Version::SDK)
        ->toBe('0.1.2')
        ->and(Version::PROTOCOL)
        ->toBe('2026-07-28')
        ->and(Version::SCHEMA_COMMIT)
        ->toHaveLength(40);
});
