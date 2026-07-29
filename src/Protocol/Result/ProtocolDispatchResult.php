<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol\Result;

interface ProtocolDispatchResult
{
    public function status(): int;

    /**
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * @return null|array<string, mixed>
     */
    public function payload(): ?array;
}
