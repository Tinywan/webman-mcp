<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tests\Fixtures;

use Tinywan\Mcp\Contracts\ResourceAuthorizerInterface;
use Tinywan\Mcp\Resource\ResourceDefinition;
use Tinywan\Mcp\Resource\ResourceTemplateDefinition;
use Tinywan\Mcp\Security\Principal;

final readonly class SelectiveResourceAuthorizer implements ResourceAuthorizerInterface
{
    /**
     * @param list<string> $listable
     * @param list<string> $readable
     */
    public function __construct(
        private array $listable,
        private array $readable,
    ) {}

    public function canListResource(Principal $principal, ResourceDefinition $resource): bool
    {
        return in_array($resource->uri, $this->listable, strict: true);
    }

    public function canReadResource(Principal $principal, ResourceDefinition $resource): bool
    {
        return in_array($resource->uri, $this->readable, strict: true);
    }

    public function canListTemplate(Principal $principal, ResourceTemplateDefinition $template): bool
    {
        return in_array($template->uriTemplate, $this->listable, strict: true);
    }

    public function canReadTemplate(Principal $principal, ResourceTemplateDefinition $template, string $uri): bool
    {
        return in_array($template->uriTemplate, $this->readable, strict: true);
    }
}
