<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

use Tinywan\Mcp\Contracts\AuthorizerInterface;
use Tinywan\Mcp\Tool\ToolDefinition;

final class DenyAllAuthorizer implements AuthorizerInterface
{
    public function canList(Principal $principal, ToolDefinition $tool): bool
    {
        return false;
    }

    public function canCall(Principal $principal, ToolDefinition $tool): bool
    {
        return false;
    }
}
