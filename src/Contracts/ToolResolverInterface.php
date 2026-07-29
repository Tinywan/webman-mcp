<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Registry\RegisteredTool;

interface ToolResolverInterface
{
    public function resolve(RegisteredTool $tool): ToolInterface;
}
