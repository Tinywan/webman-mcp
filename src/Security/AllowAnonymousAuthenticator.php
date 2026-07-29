<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

use Tinywan\Mcp\Contracts\AuthenticatorInterface;
use Tinywan\Mcp\Transport\HttpRequestContext;

final class AllowAnonymousAuthenticator implements AuthenticatorInterface
{
    public function authenticate(HttpRequestContext $request): Principal
    {
        return Principal::anonymous();
    }
}
