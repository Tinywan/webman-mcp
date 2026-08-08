<?php

declare(strict_types=1);

use Tinywan\Mcp\Examples\Assistant\AssistantServer;
use Tinywan\Mcp\Prompt\FactoryPromptResolver;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Resource\FactoryResourceResolver;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Transport\HttpRequestContext;
use Tinywan\Mcp\Transport\StreamableHttpTransport;

/** @param array<string, mixed> $params @return array<string, mixed> */
function assistant_call(string $method, array $params = []): array
{
    $transport = new StreamableHttpTransport(
        new ServerRegistry([AssistantServer::definition()]),
        new FactoryToolResolver(),
        new FactoryResourceResolver(),
        new FactoryPromptResolver(),
    );
    $body = json_encode([
        'jsonrpc' => '2.0',
        'id' => 12,
        'method' => $method,
        'params' => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => '2026-07-28',
                'io.modelcontextprotocol/clientCapabilities' => (object) [],
            ],
            ...$params,
        ],
    ], JSON_THROW_ON_ERROR);
    $response = $transport->handle(
        new HttpRequestContext(
            'POST',
            '/mcp/assistant',
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json, text/event-stream',
                'MCP-Protocol-Version' => '2026-07-28',
                'Mcp-Method' => $method,
            ],
            $body,
        ),
    );
    /** @var mixed $decoded */
    $decoded = json_decode($response->body, associative: true, flags: JSON_THROW_ON_ERROR);
    assert(is_array($decoded) && !array_is_list($decoded), description: 'Assistant response must be an object.');

    /** @var array<string, mixed> $decoded */
    return $decoded;
}

/** @return array<string, mixed> */
function assistant_object(mixed $value): array
{
    assert(is_array($value) && !array_is_list($value), description: 'Assistant value must be an object.');

    /** @var array<string, mixed> $value */
    return $value;
}

it('discovers, lists, renders, and completes Assistant Prompts end to end', function (): void {
    $discovery = assistant_call('server/discover');
    $listing = assistant_call('prompts/list');
    $rendered = assistant_call('prompts/get', ['name' => 'greet', 'arguments' => ['name' => 'Ada']]);
    $completed = assistant_call('completion/complete', [
        'ref' => ['type' => 'ref/prompt', 'name' => 'greet'],
        'argument' => ['name' => 'name', 'value' => 'Al'],
    ]);
    assert(is_array($discovery['result']) && is_array($listing['result']), description: 'List results are required.');
    assert(
        is_array($rendered['result']) && is_array($completed['result']),
        description: 'Action results are required.',
    );
    $completion = assistant_object($completed['result']['completion'] ?? null);

    expect($discovery['result']['capabilities'])
        ->toBe(['prompts' => ['listChanged' => false], 'completions' => []])
        ->and($listing['result']['prompts'])
        ->toHaveCount(1)
        ->and($rendered['result']['messages'])
        ->toHaveCount(1)
        ->and($completion['values'])
        ->toBe(['Alan', 'Alice']);
});
