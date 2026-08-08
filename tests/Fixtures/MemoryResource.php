<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use RuntimeException;
use Tinywan\Mcp\Contracts\ResourceHandlerInterface;
use Tinywan\Mcp\Resource\Content\TextResourceContent;
use Tinywan\Mcp\Resource\ResourceReadCall;
use Tinywan\Mcp\Resource\ResourceReadResult;
use Tinywan\Mcp\Runtime\ExecutionContext;

final class MemoryResource implements ResourceHandlerInterface
{
    public static int $instances = 0;

    public function __construct()
    {
        self::$instances++;
    }

    public function read(ResourceReadCall $call, ExecutionContext $context): ResourceReadResult
    {
        if ($call->uri === 'memory://throw') {
            throw new RuntimeException('sensitive resource failure');
        }

        return new ResourceReadResult([
            new TextResourceContent($call->uri, "{$context->principal->id}:{$call->uri}", 'text/plain'),
        ]);
    }
}
