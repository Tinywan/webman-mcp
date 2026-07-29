<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool;

use RuntimeException;
use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Contracts\ToolResolverInterface;
use Tinywan\Mcp\Registry\RegisteredTool;
use Webman\Container;

final readonly class ContainerToolResolver implements ToolResolverInterface
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolve(RegisteredTool $tool): ToolInterface
    {
        return $this->requireTool($this->container->make($tool->handlerClass), $tool->handlerClass);
    }

    private function requireTool(mixed $handler, string $class): ToolInterface
    {
        if (!$handler instanceof ToolInterface) {
            throw new RuntimeException("Tool handler '{$class}' must implement ToolInterface.");
        }

        return $handler;
    }
}
