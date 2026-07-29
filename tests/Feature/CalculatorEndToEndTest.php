<?php

declare(strict_types=1);

use Tinywan\Mcp\Examples\Calculator\CalculatorServer;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Transport\HttpRequestContext;
use Tinywan\Mcp\Transport\HttpResponse;
use Tinywan\Mcp\Transport\StreamableHttpTransport;

function calculator_transport(): StreamableHttpTransport
{
    return new StreamableHttpTransport(new ServerRegistry([CalculatorServer::definition()]), new FactoryToolResolver());
}

/**
 * @param array<string, mixed> $params
 */
function calculator_request(string $method, array $params = []): HttpRequestContext
{
    $body = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => $method,
        'params' => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
            ],
            ...$params,
        ],
    ], JSON_THROW_ON_ERROR);
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json, text/event-stream',
        'MCP-Protocol-Version' => '2026-07-28',
        'Mcp-Method' => $method,
    ];
    if ($method === 'tools/call') {
        $headers['Mcp-Name'] = 'calculate';
    }

    return new HttpRequestContext('POST', '/mcp/calculator', $headers, $body);
}

/**
 * @return array<string, mixed>
 */
function calculator_json(HttpResponse $response): array
{
    return calculator_json_array(json_decode($response->body, associative: true, flags: JSON_THROW_ON_ERROR));
}

/**
 * @return array<string, mixed>
 */
function calculator_json_array(mixed $value): array
{
    assert(is_array($value) && !array_is_list($value), description: 'Calculator response must be a JSON object.');

    /** @var array<string, mixed> $value */
    return $value;
}

it('runs Calculator discovery, listing, and a valid call end to end', function (): void {
    $transport = calculator_transport();
    $discovery = calculator_json($transport->handle(calculator_request('server/discover')));
    $listing = calculator_json($transport->handle(calculator_request('tools/list')));
    $call = calculator_json($transport->handle(calculator_request('tools/call', [
        'name' => 'calculate',
        'arguments' => ['operation' => 'multiply', 'left' => 6, 'right' => 7],
    ])));
    assert(is_array($discovery['result']), description: 'Discovery result must be an object.');
    assert(is_array($listing['result']), description: 'List result must be an object.');
    assert(is_array($call['result']), description: 'Call result must be an object.');

    expect($discovery['result']['capabilities'])
        ->toBe(['tools' => ['listChanged' => false]])
        ->and($listing['result']['tools'])
        ->toHaveCount(1)
        ->and($call['result']['structuredContent'])
        ->toBe(['value' => 42]);
});

it('returns invalid params without invoking Calculator for a schema violation', function (): void {
    $response = calculator_transport()->handle(calculator_request('tools/call', [
        'name' => 'calculate',
        'arguments' => ['operation' => 'add', 'left' => 'six', 'right' => 7],
    ]));
    $json = calculator_json($response);
    assert(is_array($json['error']), description: 'Invalid Calculator arguments must return an error object.');

    expect($response->status)->toBe(400)->and($json['error']['code'])->toBe(-32_602);
});
