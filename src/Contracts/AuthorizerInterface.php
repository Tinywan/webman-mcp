<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Tool\ToolDefinition;

interface AuthorizerInterface
{
    public function canList(Principal $principal, ToolDefinition $tool): bool;

    public function canCall(Principal $principal, ToolDefinition $tool): bool;
}
