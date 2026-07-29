<?php

declare(strict_types=1);

use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Protocol\ClientCapabilities;
use Tinywan\Mcp\Protocol\NativeProtocolDriver;
use Tinywan\Mcp\Protocol\ProtocolRequest;
use Tinywan\Mcp\Protocol\RequestId;
use Tinywan\Mcp\Protocol\Result\AcceptedResult;
use Tinywan\Mcp\Protocol\Result\JsonResult;
use Tinywan\Mcp\Protocol\Result\StreamResult;
use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerOptions;
use Tinywan\Mcp\Runtime\Deadline;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;
use Tinywan\Mcp\Security\Principal;
use Tinywan\Mcp\Tests\Fixtures\EchoTool;
use Tinywan\Mcp\Tests\Fixtures\SelectiveAuthorizer;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Tool\Schema\ToolSchemaValidator;
use Tinywan\Mcp\Tool\ToolRuntime;

/**
 * @param list<string> $listable
 * @param list<string> $callable
 */
function native_driver(array $listable = ['echo'], array $callable = ['echo']): NativeProtocolDriver
{
    $echo = new EchoTool();
    $server = new ServerDefinition(
        'native',
        '/native',
        new ServerIdentity('Native Server', '0.1.0', 'Native'),
        [new RegisteredTool($echo->definition(), EchoTool::class)],
        new AllowAnonymousAuthenticator(),
        new SelectiveAuthorizer($listable, $callable),
        new ServerOptions(instructions: 'Use visible tools only.'),
    );

    return new NativeProtocolDriver($server, new ToolRuntime(new ToolSchemaValidator(), new FactoryToolResolver()));
}

function native_context(string $principal = 'user-1'): ExecutionContext
{
    return new ExecutionContext(
        new Principal($principal),
        'trace-native',
        '2026-07-28',
        null,
        new ClientCapabilities([]),
        Deadline::none(),
    );
}

/**
 * @param array<string, mixed> $params
 */
function native_request(string $method, array $params = [], string $version = '2026-07-28'): ProtocolRequest
{
    return new ProtocolRequest(
        RequestId::from(7),
        $method,
        ['_meta' => [], ...$params],
        $version,
        new ClientCapabilities([]),
    );
}

it('discovers fixed identity, version, instructions, and visible capabilities', function (): void {
    $result = native_driver()->dispatch(native_request('server/discover'), native_context());
    $payload = $result->payload();
    assert(is_array($payload), description: 'Discovery must return a JSON payload.');
    assert(is_array($payload['result']), description: 'Discovery must contain a result object.');

    expect($result)
        ->toBeInstanceOf(JsonResult::class)
        ->and($payload['result']['supportedVersions'])
        ->toBe(['2026-07-28'])
        ->and($payload['result']['_meta'])
        ->toBe(['io.modelcontextprotocol/serverInfo' => [
            'name' => 'Native Server',
            'version' => '0.1.0',
            'title' => 'Native',
        ]])
        ->and($payload['result']['capabilities'])
        ->toBe(['tools' => ['listChanged' => false]])
        ->and($payload['result']['instructions'])
        ->toBe('Use visible tools only.')
        ->and($payload['result']['cacheScope'])
        ->toBe('private');
});

it('hides Tool capability and definitions from a Principal without list access', function (): void {
    $driver = native_driver([]);
    $discovery = $driver->dispatch(native_request('server/discover'), native_context('hidden'))->payload();
    $listing = $driver->dispatch(native_request('tools/list'), native_context('hidden'))->payload();
    assert(is_array($discovery) && is_array($discovery['result']), description: 'Discovery payload is required.');
    assert(is_array($listing) && is_array($listing['result']), description: 'Tool list payload is required.');

    expect($discovery['result']['capabilities'])->toBe([])->and($listing['result']['tools'])->toBe([]);
});

it('routes Tool calls and preserves the request ID', function (): void {
    $result = native_driver()->dispatch(native_request('tools/call', [
        'name' => 'echo',
        'arguments' => ['message' => 'hello'],
    ]), native_context());
    $payload = $result->payload();
    assert(is_array($payload) && is_array($payload['result']), description: 'Tool call payload is required.');

    expect($payload['id'])
        ->toBe(7)
        ->and($payload['result']['resultType'])
        ->toBe('complete')
        ->and($payload['result']['structuredContent'])
        ->toMatchArray(['message' => 'hello', 'principal' => 'user-1']);
});

it('returns deterministic errors for unsupported versions and methods', function (): void {
    $unsupported = native_driver()->dispatch(native_request('tools/list', version: '2025-11-25'), native_context());
    $unknown = native_driver()->dispatch(native_request('initialize'), native_context());
    $unsupportedPayload = $unsupported->payload();
    $unknownPayload = $unknown->payload();
    assert(is_array($unsupportedPayload), description: 'Unsupported version must return JSON.');
    assert(is_array($unknownPayload), description: 'Unknown method must return JSON.');

    expect($unsupported->status())
        ->toBe(400)
        ->and($unsupportedPayload['error'])
        ->toMatchArray(['code' => -32_022])
        ->and($unknown->status())
        ->toBe(404)
        ->and($unknownPayload['error'])
        ->toMatchArray(['code' => -32_601]);
});

/** @var TestCall $notificationResults */
$notificationResults = it('maps every notification to 202 without a stream or payload', function (string $method): void {
    $request = native_request($method);
    $notification = new ProtocolRequest(
        null,
        $request->method,
        $request->params,
        $request->protocolVersion,
        $request->clientCapabilities,
    );
    $result = native_driver()->dispatch($notification, native_context());

    expect($result)
        ->toBeInstanceOf(AcceptedResult::class)
        ->and($result instanceof StreamResult)
        ->toBeFalse()
        ->and($result->status())
        ->toBe(202)
        ->and($result->payload())
        ->toBeNull();
});
assert($notificationResults instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$notificationResults->with([
    ['server/discover'],
    ['tools/list'],
    ['initialize'],
]);
unset($notificationResults);
