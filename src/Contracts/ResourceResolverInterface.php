<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Registry\RegisteredResource;
use Tinywan\Mcp\Registry\RegisteredResourceTemplate;

interface ResourceResolverInterface
{
    public function resolve(RegisteredResource|RegisteredResourceTemplate $resource): ResourceHandlerInterface;
}
