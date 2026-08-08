<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

interface NotificationInterface
{
    public function method(): string;

    /** @return array<string, mixed> */
    public function params(): array;
}
