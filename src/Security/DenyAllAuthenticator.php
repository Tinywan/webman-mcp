<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

use Tinywan\Mcp\Contracts\AuthenticatorInterface;
use Tinywan\Mcp\Transport\HttpRequestContext;

final class DenyAllAuthenticator implements AuthenticatorInterface
{
    public function authenticate(HttpRequestContext $request): Principal
    {
        throw new AuthenticationException('Authentication is required.');
    }
}
