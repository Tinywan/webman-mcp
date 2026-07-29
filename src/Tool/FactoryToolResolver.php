<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool;

use ReflectionClass;
use RuntimeException;
use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Contracts\ToolResolverInterface;
use Tinywan\Mcp\Registry\RegisteredTool;

final class FactoryToolResolver implements ToolResolverInterface
{
    public function resolve(RegisteredTool $tool): ToolInterface
    {
        $class = $tool->handlerClass;
        $reflection = new ReflectionClass($class);
        if (!$reflection->isInstantiable()) {
            throw new RuntimeException("Tool handler '{$class}' must be instantiable.");
        }

        return $reflection->newInstance();
    }
}
