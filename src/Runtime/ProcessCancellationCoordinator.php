<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Runtime;

use Tinywan\Mcp\Contracts\CancellationCoordinatorInterface;
use Tinywan\Mcp\Protocol\RequestId;

final class ProcessCancellationCoordinator implements CancellationCoordinatorInterface
{
    /** @var array<string, CancellationToken> */
    private array $tokens = [];

    public function register(RequestId $requestId, #[\SensitiveParameter] CancellationToken $token): bool
    {
        $key = self::key($requestId);
        if (array_key_exists($key, $this->tokens)) {
            return false;
        }

        $this->tokens[$key] = $token;

        return true;
    }

    public function cancel(RequestId $requestId, ?string $reason = null): bool
    {
        $token = $this->tokens[self::key($requestId)] ?? null;
        if ($token === null) {
            return false;
        }

        $token->cancel($reason);

        return true;
    }

    public function release(RequestId $requestId): void
    {
        unset($this->tokens[self::key($requestId)]);
    }

    private static function key(RequestId $requestId): string
    {
        $value = $requestId->value();

        return (is_int($value) ? 'i:' : 's:') . $value;
    }
}
