<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Protocol\Result;

final readonly class AcceptedResult implements ProtocolDispatchResult
{
    public function status(): int
    {
        return 202;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return [];
    }

    public function payload(): null
    {
        return null;
    }
}
