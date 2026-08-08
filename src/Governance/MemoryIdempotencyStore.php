<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Governance;

use Tinywan\Mcp\Contracts\IdempotencyStoreInterface;

final class MemoryIdempotencyStore implements IdempotencyStoreInterface
{
    /** @var array<string, IdempotencyRecord> */
    private array $records = [];

    public function find(string $key): ?IdempotencyRecord
    {
        $record = $this->records[$key] ?? null;
        if ($record === null || $record->expiresAt <= time()) {
            unset($this->records[$key]);

            return null;
        }

        return $record;
    }

    public function store(string $key, IdempotencyRecord $record): void
    {
        $this->records[$key] = $record;
    }
}
