<?php

declare(strict_types=1);

use Tinywan\Mcp\Protocol\ClientCapabilities;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Runtime\Deadline;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Tests\Fixtures\BusinessFailureTool;
use Tinywan\Mcp\Tests\Fixtures\EchoTool;
use Tinywan\Mcp\Tests\Fixtures\InvalidOutputTool;
use Tinywan\Mcp\Tests\Fixtures\SelectiveAuthorizer;
use Tinywan\Mcp\Tests\Fixtures\ThrowingTool;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Tool\Schema\ToolSchemaValidator;
use Tinywan\Mcp\Tool\ToolRuntime;

function runtime_context(
    string $principal = 'user-1',
    string $trace = 'trace-1',
    ?Deadline $deadline = null,
): ExecutionContext {
    return new ExecutionContext(
        new Principal($principal),
        $trace,
        '2026-07-28',
        null,
        new ClientCapabilities([]),
        $deadline ?? Deadline::none(),
    );
}

/**
 * @param list<string> $listable
 * @param list<string> $callable
 */
function runtime_server(array $listable, array $callable): ServerDefinition
{
    $echo = new EchoTool();
    $businessFailure = new BusinessFailureTool();
    $throwing = new ThrowingTool();
    $invalidOutput = new InvalidOutputTool();
    $tools = [
        new RegisteredTool($echo->definition(), EchoTool::class),
        new RegisteredTool($businessFailure->definition(), BusinessFailureTool::class),
        new RegisteredTool($throwing->definition(), ThrowingTool::class),
        new RegisteredTool($invalidOutput->definition(), InvalidOutputTool::class),
    ];

    return new ServerDefinition(
        'runtime',
        '/runtime',
        new ServerIdentity('Runtime', '0.1.0'),
        $tools,
        new AllowAnonymousAuthenticator(),
        new SelectiveAuthorizer($listable, $callable),
    );
}

function tool_runtime(): ToolRuntime
{
    return new ToolRuntime(new ToolSchemaValidator(), new FactoryToolResolver());
}

it('filters Tool definitions independently from call authorization', function (): void {
    $server = runtime_server(['echo'], ['business-failure']);
    $runtime = tool_runtime();

    expect(array_map(static fn($tool): string => $tool->name, $runtime->list($server, runtime_context())))
        ->toBe(['echo'])
        ->and(fn() => $runtime->call($server, 'echo', ['message' => 'hidden'], runtime_context(), RequestId::from(1)))
        ->toThrow(ProtocolException::class, 'Unknown or unauthorized tool');
});

it('validates arguments before resolving a handler', function (): void {
    EchoTool::$instances = 0;
    $server = runtime_server(['echo'], ['echo']);
    $instancesAfterRegistration = EchoTool::$instances;

    expect(fn() => tool_runtime()->call($server, 'echo', ['message' => 123], runtime_context(), RequestId::from(1)))
        ->toThrow(ProtocolException::class, 'Invalid tool arguments')
        ->and(EchoTool::$instances)
        ->toBe($instancesAfterRegistration);
});

it('resolves a fresh handler and isolates interleaved Principals', function (): void {
    EchoTool::$instances = 0;
    $server = runtime_server(['echo'], ['echo']);
    $runtime = tool_runtime();

    $first = $runtime->call($server, 'echo', ['message' => 'one'], runtime_context('user-1'), RequestId::from(1));
    $second = $runtime->call($server, 'echo', ['message' => 'two'], runtime_context('user-2'), RequestId::from(2));
    assert(is_array($first->structuredContent), description: 'Echo results must contain structured content.');
    assert(is_array($second->structuredContent), description: 'Echo results must contain structured content.');

    expect($first->structuredContent['principal'])
        ->toBe('user-1')
        ->and($second->structuredContent['principal'])
        ->toBe('user-2')
        ->and($first->structuredContent['instance'])
        ->not->toBe($second->structuredContent['instance']);
});

it('returns business failures as Tool results', function (): void {
    $result = tool_runtime()->call(
        runtime_server([], ['business-failure']),
        'business-failure',
        [],
        runtime_context(),
        RequestId::from(1),
    );

    expect($result->toArray()['resultType'])->toBe('complete')->and($result->toArray()['isError'])->toBeTrue();
});

/** @var \Pest\PendingCalls\TestCall $sanitizedFailures */
$sanitizedFailures = it('sanitizes handler exceptions and invalid output', function (string $name): void {
    try {
        tool_runtime()->call(
            runtime_server([], [$name]),
            $name,
            [],
            runtime_context(trace: 'trace-safe'),
            RequestId::from(1),
        );
        throw new RuntimeException('Expected a protocol error.');
    } catch (ProtocolException $exception) {
        expect($exception->error->code)
            ->toBe(-32_603)
            ->and($exception->error->message)
            ->toBe('Internal error')
            ->and($exception->error->data)
            ->toBe(['traceId' => 'trace-safe'])
            ->and(json_encode($exception->error->toEnvelope(), JSON_THROW_ON_ERROR))
            ->not->toContain('password=secret');
    }
});
assert(
    $sanitizedFailures instanceof \Pest\PendingCalls\TestCall,
    description: 'Pest must return a dataset-capable TestCall.',
);
$sanitizedFailures->with([
    ['throwing'],
    ['invalid-output'],
]);
unset($sanitizedFailures);

it('rejects an expired cooperative deadline before resolving a Tool', function (): void {
    $expired = new Deadline(new DateTimeImmutable('-1 second'));

    expect(fn() => tool_runtime()->call(
        runtime_server([], ['echo']),
        'echo',
        ['message' => 'late'],
        runtime_context(deadline: $expired),
        RequestId::from(1),
    ))
        ->toThrow(ProtocolException::class, 'Internal error');
});
