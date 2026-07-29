<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Transport\HttpRequestContext;

interface AuthenticatorInterface
{
    public function authenticate(HttpRequestContext $request): Principal;
}
