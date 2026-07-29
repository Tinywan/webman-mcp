<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Tool\ToolDefinition;

final readonly class RegisteredTool
{
    /**
     * @param class-string<ToolInterface> $handlerClass
     */
    public function __construct(
        public ToolDefinition $definition,
        public string $handlerClass,
    ) {}
}
