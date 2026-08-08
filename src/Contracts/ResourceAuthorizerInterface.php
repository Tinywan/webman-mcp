<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Contracts;

use Tinywan\Mcp\Resource\ResourceDefinition;
use Tinywan\Mcp\Resource\ResourceTemplateDefinition;
use Tinywan\Mcp\Security\Principal;

interface ResourceAuthorizerInterface
{
    public function canListResource(Principal $principal, ResourceDefinition $resource): bool;

    public function canReadResource(Principal $principal, ResourceDefinition $resource): bool;

    public function canListTemplate(Principal $principal, ResourceTemplateDefinition $template): bool;

    public function canReadTemplate(Principal $principal, ResourceTemplateDefinition $template, string $uri): bool;
}
