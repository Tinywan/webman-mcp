<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Security;

use Tinywan\Mcp\Contracts\ResourceAuthorizerInterface;
use Tinywan\Mcp\Resource\ResourceDefinition;
use Tinywan\Mcp\Resource\ResourceTemplateDefinition;

final class DenyAllResourceAuthorizer implements ResourceAuthorizerInterface
{
    public function canListResource(Principal $principal, ResourceDefinition $resource): bool
    {
        return false;
    }

    public function canReadResource(Principal $principal, ResourceDefinition $resource): bool
    {
        return false;
    }

    public function canListTemplate(Principal $principal, ResourceTemplateDefinition $template): bool
    {
        return false;
    }

    public function canReadTemplate(Principal $principal, ResourceTemplateDefinition $template, string $uri): bool
    {
        return false;
    }
}
