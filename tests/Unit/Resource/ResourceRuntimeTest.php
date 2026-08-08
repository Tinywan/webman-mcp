<?php

declare(strict_types=1);

use Tinywan\Mcp\Protocol\ClientCapabilities;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Registry\RegisteredResource;
use Tinywan\Mcp\Registry\RegisteredResourceTemplate;
use Tinywan\Mcp\Registry\ResourceRegistration;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerFeatures;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerOptions;
use Tinywan\Mcp\Resource\FactoryResourceResolver;
use Tinywan\Mcp\Resource\ResourceAnnotations;
use Tinywan\Mcp\Resource\ResourceDefinition;
use Tinywan\Mcp\Resource\ResourceRuntime;
use Tinywan\Mcp\Resource\ResourceTemplateDefinition;
use Tinywan\Mcp\Runtime\Deadline;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Tests\Fixtures\MemoryResource;
use Tinywan\Mcp\Tests\Fixtures\SelectiveResourceAuthorizer;

function resource_context(string $principal = 'reader'): ExecutionContext
{
    return new ExecutionContext(
        new Principal($principal),
        'trace-resource',
        '2026-07-28',
        null,
        new ClientCapabilities([]),
        Deadline::none(),
    );
}

/** @return array<string, mixed> */
function resource_result_object(mixed $value): array
{
    assert(is_array($value) && !array_is_list($value), description: 'Resource result value must be an object.');

    /** @var array<string, mixed> $value */
    return $value;
}

/** @return list<mixed> */
function resource_result_list(mixed $value): array
{
    assert(is_array($value) && array_is_list($value), description: 'Resource result value must be a list.');

    return $value;
}

/**
 * @param list<string> $listable
 * @param list<string> $readable
 */
function resource_server(
    array $listable,
    array $readable,
    int $pageSize = 1,
    int $responseLimit = 4_096,
): ServerDefinition {
    $uris = ['memory://one', 'memory://two'];
    $resources = array_map(
        static fn(string $uri): RegisteredResource => new RegisteredResource(
            new ResourceDefinition($uri, basename($uri), mimeType: 'text/plain'),
            MemoryResource::class,
        ),
        $uris,
    );
    $templates = [new RegisteredResourceTemplate(
        new ResourceTemplateDefinition('memory://users/{id}', 'user', mimeType: 'text/plain'),
        MemoryResource::class,
    )];

    return new ServerDefinition(
        'resources',
        '/resources',
        new ServerIdentity('Resources', '0.1.0'),
        [],
        options: new ServerOptions(resources: new \Tinywan\Mcp\Registry\ResourceLimits($pageSize, $responseLimit)),
        features: new ServerFeatures(
            resources: new ResourceRegistration(
                $resources,
                $templates,
                new SelectiveResourceAuthorizer($listable, $readable),
            ),
        ),
    );
}

it('paginates visible Resources with definition-bound opaque cursors', function (): void {
    $runtime = new ResourceRuntime(new FactoryResourceResolver());
    $server = resource_server(['memory://one', 'memory://two'], []);
    $first = $runtime->listResources($server, resource_context(), null, RequestId::from(1));
    assert(is_string($first['nextCursor']), description: 'The first Resource page must have a cursor.');
    $second = $runtime->listResources($server, resource_context(), $first['nextCursor'], RequestId::from(1));

    expect($first['resources'])
        ->toHaveCount(1)
        ->and($second['resources'])
        ->toHaveCount(1)
        ->and($second)
        ->not
        ->toHaveKey('nextCursor')
        ->and(fn() => $runtime->listResources($server, resource_context(), 'invalid', RequestId::from(1)))
        ->toThrow(ProtocolException::class);
});

it('serializes validated official Resource annotations', function (): void {
    $definition = new ResourceDefinition(
        'memory://annotated',
        'annotated',
        annotations: new ResourceAnnotations(['user', 'assistant'], 0.75, '2026-08-02T00:00:00+00:00'),
    );

    expect($definition->toArray()['annotations'])->toBe([
        'audience' => ['user', 'assistant'],
        'priority' => 0.75,
        'lastModified' => '2026-08-02T00:00:00+00:00',
    ]);
});

it('resolves fresh exact and template handlers for authorized reads', function (): void {
    MemoryResource::$instances = 0;
    $runtime = new ResourceRuntime(new FactoryResourceResolver());
    $server = resource_server([], ['memory://one', 'memory://users/{id}']);
    $exact = $runtime->read($server, resource_context('one'), 'memory://one', RequestId::from(2));
    $template = $runtime->read($server, resource_context('two'), 'memory://users/42', RequestId::from(3));
    $exactContents = resource_result_list($exact['contents'] ?? null);
    $templateContents = resource_result_list($template['contents'] ?? null);
    $exactContent = resource_result_object($exactContents[0] ?? null);
    $templateContent = resource_result_object($templateContents[0] ?? null);

    expect(MemoryResource::$instances)
        ->toBe(2)
        ->and($exactContent['text'])
        ->toBe('one:memory://one')
        ->and($templateContent['text'])
        ->toBe('two:memory://users/42');
});

it('does not resolve hidden Resources and sanitizes output limit failures', function (): void {
    MemoryResource::$instances = 0;
    $runtime = new ResourceRuntime(new FactoryResourceResolver());
    $hidden = resource_server([], []);
    $limited = resource_server([], ['memory://one'], responseLimit: 10);

    expect($runtime->listResources($hidden, resource_context(), null, RequestId::from(4))['resources'])
        ->toBe([])
        ->and(fn() => $runtime->read($hidden, resource_context(), 'memory://one', RequestId::from(4)))
        ->toThrow(ProtocolException::class)
        ->and(MemoryResource::$instances)
        ->toBe(0)
        ->and(fn() => $runtime->read($limited, resource_context(), 'memory://one', RequestId::from(5)))
        ->toThrow(ProtocolException::class);
});
