<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Examples\Library;

use Tinywan\Mcp\Registry\RegisteredResource;
use Tinywan\Mcp\Registry\RegisteredResourceTemplate;
use Tinywan\Mcp\Registry\ResourceRegistration;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerFeatures;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Resource\ResourceDefinition;
use Tinywan\Mcp\Resource\ResourceTemplateDefinition;
use Tinywan\Mcp\Security\AllowAllResourceAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;

final class LibraryServer
{
    public static function definition(): ServerDefinition
    {
        return new ServerDefinition(
            'library',
            '/mcp/library',
            new ServerIdentity('Library', '0.1.0'),
            [],
            new AllowAnonymousAuthenticator(),
            features: new ServerFeatures(
                resources: new ResourceRegistration(
                    [new RegisteredResource(
                        new ResourceDefinition(
                            'library://guides/getting-started',
                            'getting-started',
                            'MCP Server getting-started guide.',
                            'text/plain',
                            title: 'Getting Started',
                        ),
                        LibraryResource::class,
                    )],
                    [new RegisteredResourceTemplate(
                        new ResourceTemplateDefinition(
                            'library://profiles/{id}',
                            'profile',
                            'A profile selected by ID.',
                            'text/plain',
                        ),
                        LibraryResource::class,
                    )],
                    new AllowAllResourceAuthorizer(),
                ),
            ),
        );
    }
}
