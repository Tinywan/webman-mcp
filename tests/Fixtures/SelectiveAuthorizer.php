<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\AuthorizerInterface;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Tool\ToolDefinition;

final readonly class SelectiveAuthorizer implements AuthorizerInterface
{
    /**
     * @param list<string> $listable
     * @param list<string> $callable
     */
    public function __construct(
        private array $listable,
        private array $callable,
    ) {}

    public function canList(Principal $principal, ToolDefinition $tool): bool
    {
        return in_array($tool->name, $this->listable, strict: true);
    }

    public function canCall(Principal $principal, ToolDefinition $tool): bool
    {
        return in_array($tool->name, $this->callable, strict: true);
    }
}
