<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

use InvalidArgumentException;
use Tinywan\Mcp\Contracts\BearerTokenVerifierInterface;

final readonly class StaticBearerTokenVerifier implements BearerTokenVerifierInterface
{
    /** @var array<string, Principal> */
    private array $principalsByDigest;

    /** @param array<string, Principal> $principalsByDigest */
    public function __construct(array $principalsByDigest)
    {
        $normalized = [];
        foreach ($principalsByDigest as $digest => $principal) {
            if (preg_match('/\A[a-f0-9]{64}\z/iD', $digest) !== 1) {
                throw new InvalidArgumentException('Static Bearer token digests must be SHA-256 hex strings.');
            }
            $normalized[strtolower($digest)] = $principal;
        }
        $this->principalsByDigest = $normalized;
    }

    /** @param array<string, Principal> $principalsByToken */
    public static function fromTokens(#[\SensitiveParameter] array $principalsByToken): self
    {
        $digests = [];
        foreach ($principalsByToken as $token => $principal) {
            if ($token === '') {
                throw new InvalidArgumentException('Static Bearer tokens cannot be empty.');
            }
            $digests[hash('sha256', $token)] = $principal;
        }

        return new self($digests);
    }

    public function verify(#[\SensitiveParameter] string $token): ?Principal
    {
        $candidate = hash('sha256', $token);
        $matched = null;
        foreach ($this->principalsByDigest as $digest => $principal) {
            if (!hash_equals($digest, $candidate)) {
                continue;
            }
            $matched = $principal;
        }

        return $matched;
    }
}
