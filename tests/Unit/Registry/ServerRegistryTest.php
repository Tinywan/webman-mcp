<?php

declare(strict_types=1);

use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\RegistryException;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Security\AllowAllAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;
use Tinywan\Mcp\Security\AuthenticationException;
use Tinywan\Mcp\Tests\Fixtures\EchoTool;
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

it('denies anonymous access unless explicitly enabled', function (): void {
    $default = new ServerDefinition('default', '/default', new ServerIdentity('Default', '0.1.0'), []);
    $request = new HttpRequestContext('POST', '/default', [], '{}');

    expect(fn() => $default->authenticator->authenticate($request))
        ->toThrow(AuthenticationException::class)
        ->and(server_definition()->authenticator->authenticate($request)->anonymous)
        ->toBeTrue();
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
