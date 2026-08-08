<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Governance\IdempotencyRecord;

interface IdempotencyStoreInterface
{
    public function find(string $key): ?IdempotencyRecord;

    public function store(string $key, IdempotencyRecord $record): void;
}
