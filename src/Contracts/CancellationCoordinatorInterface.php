<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Runtime\CancellationToken;

interface CancellationCoordinatorInterface
{
    public function register(RequestId $requestId, #[\SensitiveParameter] CancellationToken $token): bool;

    public function cancel(RequestId $requestId, ?string $reason = null): bool;

    public function release(RequestId $requestId): void;
}
