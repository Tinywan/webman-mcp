<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Examples\Library;

use Tinywan\Mcp\Contracts\ResourceHandlerInterface;
use Tinywan\Mcp\Resource\Content\TextResourceContent;
use Tinywan\Mcp\Resource\ResourceReadCall;
use Tinywan\Mcp\Resource\ResourceReadResult;
use Tinywan\Mcp\Runtime\ExecutionContext;

final class LibraryResource implements ResourceHandlerInterface
{
    public function read(ResourceReadCall $call, ExecutionContext $context): ResourceReadResult
    {
        $text = $call->uri === 'library://guides/getting-started'
            ? 'Start with server discovery, then list or read an authorized capability.'
            : "Profile resource for {$call->uri}.";

        return new ResourceReadResult([
            new TextResourceContent($call->uri, $text, 'text/plain'),
        ]);
    }
}
