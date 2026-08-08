<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Security\Principal;

interface BearerTokenVerifierInterface
{
    public function verify(#[\SensitiveParameter] string $token): ?Principal;
}
