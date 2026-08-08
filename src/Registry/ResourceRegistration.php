<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Contracts\ResourceAuthorizerInterface;
use Tinywan\Mcp\Security\DenyAllResourceAuthorizer;

final readonly class ResourceRegistration
{
    public ResourceAuthorizerInterface $authorizer;

    /**
     * @param list<RegisteredResource> $resources
     * @param list<RegisteredResourceTemplate> $templates
     */
    public function __construct(
        public array $resources = [],
        public array $templates = [],
        ?ResourceAuthorizerInterface $authorizer = null,
    ) {
        $this->authorizer = $authorizer ?? new DenyAllResourceAuthorizer();
    }
}
