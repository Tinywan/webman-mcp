<?php

declare(strict_types=1);

use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Protocol\Error\ProtocolException;
use Tinywan\Mcp\Protocol\ProtocolParser;

function valid_request(array $overrides = []): string
{
    $message = array_replace_recursive([
        'jsonrpc' => '2.0',
        'id' => 'request-1',
        'method' => 'tools/list',
        'params' => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
                'io.modelcontextprotocol/clientInfo' => [
                    'name' => 'Test Client',
                    'version' => '1.0.0',
                ],
            ],
        ],
    ], $overrides);

    return json_encode($message, JSON_THROW_ON_ERROR);
}

it('parses a request with per-request metadata', function (): void {
    $request = (new ProtocolParser())->parse(valid_request());

    expect($request->id?->value())
        ->toBe('request-1')
        ->and($request->method)
        ->toBe('tools/list')
        ->and($request->protocolVersion)
        ->toBe('2026-07-28')
        ->and($request->clientCapabilities->toArray())
        ->toBe([])
        ->and($request->clientInfo?->name)
        ->toBe('Test Client')
        ->and($request->isNotification())
        ->toBeFalse();
});

it('parses a notification and optional clientInfo', function (): void {
    $request = (new ProtocolParser())->parse(<<<'JSON'
        {
            "jsonrpc": "2.0",
            "method": "tools/list",
            "params": {
                "_meta": {
                    "io.modelcontextprotocol/protocolVersion": "2026-07-28",
                    "io.modelcontextprotocol/clientCapabilities": {}
                }
            }
        }
        JSON);

    expect($request->isNotification())->toBeTrue()->and($request->clientInfo)->toBeNull();
});

/** @var TestCall $invalidMessages */
$invalidMessages = it('maps invalid messages to deterministic errors', function (string $json, int $code): void {
    try {
        (new ProtocolParser())->parse($json);
        throw new RuntimeException('Expected the parser to reject the message.');
    } catch (ProtocolException $exception) {
        expect($exception->error->code)->toBe($code);
    }
});
assert($invalidMessages instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$invalidMessages->with([
    'invalid JSON' => ['{', -32_700],
    'batch' => ['[]', -32_600],
    'response' => ['{"jsonrpc":"2.0","id":1,"result":{}}', -32_600],
    'null ID' => [valid_request(['id' => null]), -32_600],
    'float ID' => [valid_request(['id' => 1.5]), -32_600],
    'missing params' => ['{"jsonrpc":"2.0","id":1,"method":"tools/list"}', -32_602],
    'missing meta' => [valid_request(['params' => ['_meta' => null]]), -32_602],
    'missing version' => [
        valid_request(['params' => ['_meta' => ['io.modelcontextprotocol/protocolVersion' => null]]]),
        -32_602,
    ],
    'missing capabilities' => [
        valid_request(['params' => ['_meta' => ['io.modelcontextprotocol/clientCapabilities' => null]]]),
        -32_602,
    ],
]);
unset($invalidMessages);
