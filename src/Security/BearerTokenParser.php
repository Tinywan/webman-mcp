<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

final readonly class BearerTokenParser
{
    public function parse(?string $authorization): string
    {
        $matches = [];
        if (
            $authorization === null
            || str_contains($authorization, ',')
            || preg_match('/\ABearer ([A-Za-z0-9._~+\/-]+={0,2})\z/iD', $authorization, $matches) !== 1
        ) {
            throw new AuthenticationException('Invalid Bearer authorization.');
        }

        return $matches[1];
    }
}
