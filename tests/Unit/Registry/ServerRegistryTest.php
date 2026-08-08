<?php

declare(strict_types=1);

use Tinywan\Mcp\Prompt\CompletionReference;
use Tinywan\Mcp\Prompt\PromptDefinition;
use Tinywan\Mcp\Registry\PromptRegistration;
use Tinywan\Mcp\Registry\RegisteredCompletion;
use Tinywan\Mcp\Registry\RegisteredPrompt;
use Tinywan\Mcp\Registry\RegisteredResource;
use Tinywan\Mcp\Registry\RegisteredResourceTemplate;
use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\RegistryException;
use Tinywan\Mcp\Registry\ResourceRegistration;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerFeatures;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Resource\ResourceDefinition;
use Tinywan\Mcp\Resource\ResourceTemplateDefinition;
use Tinywan\Mcp\Security\AllowAllAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;
use Tinywan\Mcp\Security\AuthenticationException;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Subscription\SubscriptionFilter;
use Tinywan\Mcp\Tests\Fixtures\EchoTool;
use Tinywan\Mcp\Tests\Fixtures\MemoryResource;
use Tinywan\Mcp\Tests\Fixtures\TestPrompt;
use Tinywan\Mcp\Transport\HttpRequestContext;

function registered_echo(): RegisteredTool
{
    $handler = new EchoTool();

    return new RegisteredTool($handler->definition(), EchoTool::class);
}

/**
 * @param null|list<RegisteredTool> $tools
 */
function server_definition(string $id = 'primary', string $path = '/mcp', ?array $tools = null): ServerDefinition
{
    return new ServerDefinition(
        $id,
        $path,
        new ServerIdentity('Test Server', '0.1.0'),
        $tools ?? [registered_echo()],
        new AllowAnonymousAuthenticator(),
        new AllowAllAuthorizer(),
    );
}

it('resolves immutable Servers by normalized ID and path', function (): void {
    $registry = new ServerRegistry([server_definition(path: 'mcp/')]);

    expect($registry->byId('primary')?->path)
        ->toBe('/mcp')
        ->and($registry->byPath('/mcp/')?->id)
        ->toBe('primary')
        ->and($registry->servers())
        ->toHaveCount(1);
});

it('rejects duplicate Server IDs and paths before serving', function (): void {
    expect(fn(): ServerRegistry => new ServerRegistry([
        server_definition('same', path: '/one'),
        server_definition('same', path: '/two'),
    ]))
        ->toThrow(RegistryException::class, 'Duplicate Server ID')
        ->and(fn(): ServerRegistry => new ServerRegistry([
            server_definition('one', path: '/same'),
            server_definition('two', path: 'same/'),
        ]))
        ->toThrow(RegistryException::class, 'Duplicate Server path');
});

it('rejects duplicate Tool names within a Server', function (): void {
    expect(fn(): ServerRegistry => new ServerRegistry([
        server_definition(tools: [registered_echo(), registered_echo()]),
    ]))
        ->toThrow(RegistryException::class, 'Duplicate Tool name');
});

it('rejects duplicate Resource URIs and Template patterns before serving', function (): void {
    $resource = new RegisteredResource(new ResourceDefinition('memory://same', 'same'), MemoryResource::class);
    $template = new RegisteredResourceTemplate(
        new ResourceTemplateDefinition('memory://items/{id}', 'item'),
        MemoryResource::class,
    );

    expect(fn(): ServerRegistry => new ServerRegistry([new ServerDefinition(
        'duplicate-resource',
        '/resource',
        new ServerIdentity('Duplicate Resource', '0.1.0'),
        [],
        features: new ServerFeatures(resources: new ResourceRegistration([$resource, $resource])),
    )]))
        ->toThrow(RegistryException::class, 'Duplicate Resource URI')
        ->and(fn(): ServerRegistry => new ServerRegistry([new ServerDefinition(
            'duplicate-template',
            '/template',
            new ServerIdentity('Duplicate Template', '0.1.0'),
            [],
            features: new ServerFeatures(resources: new ResourceRegistration(templates: [$template, $template])),
        )]))
        ->toThrow(RegistryException::class, 'Duplicate Resource Template');
});

it('rejects duplicate Prompt names and Completion references before serving', function (): void {
    $prompt = new RegisteredPrompt(new PromptDefinition('same'), TestPrompt::class);
    $completion = new RegisteredCompletion(new CompletionReference('ref/prompt', 'same'), TestPrompt::class);

    expect(fn(): ServerRegistry => new ServerRegistry([new ServerDefinition(
        'duplicate-prompt',
        '/prompt',
        new ServerIdentity('Duplicate Prompt', '0.1.0'),
        [],
        features: new ServerFeatures(prompts: new PromptRegistration([$prompt, $prompt])),
    )]))
        ->toThrow(RegistryException::class, 'Duplicate Prompt name')
        ->and(fn(): ServerRegistry => new ServerRegistry([new ServerDefinition(
            'duplicate-completion',
            '/completion',
            new ServerIdentity('Duplicate Completion', '0.1.0'),
            [],
            features: new ServerFeatures(prompts: new PromptRegistration(completions: [$completion, $completion])),
        )]))
        ->toThrow(RegistryException::class, 'Duplicate Completion reference');
});

it('denies anonymous access unless explicitly enabled', function (): void {
    $default = new ServerDefinition('default', '/default', new ServerIdentity('Default', '0.1.0'), []);
    $request = new HttpRequestContext('POST', '/default', [], '{}');

    expect(fn() => $default->authenticator->authenticate($request))
        ->toThrow(AuthenticationException::class)
        ->and(server_definition()->authenticator->authenticate($request)->anonymous)
        ->toBeTrue()
        ->and($default->features->resources->authorizer->canListResource(
            new Principal('anonymous', anonymous: true),
            new ResourceDefinition('memory://hidden', 'hidden'),
        ))
        ->toBeFalse()
        ->and($default->features->prompts->authorizer->canList(
            new Principal('anonymous', anonymous: true),
            new PromptDefinition('hidden'),
        ))
        ->toBeFalse()
        ->and($default->features->subscriptions->authorizer->canListen(
            new Principal('anonymous', anonymous: true),
            new SubscriptionFilter(toolsListChanged: true),
        ))
        ->toBeFalse();
});

it('constructs equivalent registries without shared request state', function (): void {
    $first = new ServerRegistry([server_definition()]);
    $second = new ServerRegistry([server_definition()]);

    expect($first)
        ->not
        ->toBe($second)
        ->and($first->byId('primary')?->identity->toArray())
        ->toBe($second->byId('primary')?->identity->toArray());
});
