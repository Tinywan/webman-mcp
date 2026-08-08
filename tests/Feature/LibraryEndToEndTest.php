<?php

declare(strict_types=1);

use Tinywan\Mcp\Examples\Library\LibraryServer;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Resource\FactoryResourceResolver;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Transport\HttpRequestContext;
use Tinywan\Mcp\Transport\StreamableHttpTransport;

/**
 * @param array<string, mixed> $params
 * @return array<string, mixed>
 */
function library_call(string $method, array $params = []): array
{
    $transport = new StreamableHttpTransport(
        new ServerRegistry([LibraryServer::definition()]),
        new FactoryToolResolver(),
        new FactoryResourceResolver(),
    );
    $body = json_encode([
        'jsonrpc' => '2.0',
        'id' => 8,
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
            '/mcp/library',
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
    assert(is_array($decoded) && !array_is_list($decoded), description: 'Library response must be an object.');

    /** @var array<string, mixed> $decoded */
    return $decoded;
}

/** @return array<string, mixed> */
function library_object(mixed $value): array
{
    assert(is_array($value) && !array_is_list($value), description: 'Library value must be an object.');

    /** @var array<string, mixed> $value */
    return $value;
}

/** @return list<mixed> */
function library_list(mixed $value): array
{
    assert(is_array($value) && array_is_list($value), description: 'Library value must be a list.');

    return $value;
}

it('discovers, lists, templates, and reads Library Resources end to end', function (): void {
    $discovery = library_call('server/discover');
    $resources = library_call('resources/list');
    $templates = library_call('resources/templates/list');
    $read = library_call('resources/read', ['uri' => 'library://profiles/42']);
    assert(
        is_array($discovery['result']) && is_array($resources['result']),
        description: 'Discovery and Resource list results must be objects.',
    );
    assert(
        is_array($templates['result']) && is_array($read['result']),
        description: 'Template list and Resource read results must be objects.',
    );
    $readResult = library_object($read['result']);
    $contents = library_list($readResult['contents'] ?? null);
    $firstContent = library_object($contents[0] ?? null);

    expect($discovery['result']['capabilities'])
        ->toBe(['resources' => ['listChanged' => false, 'subscribe' => false]])
        ->and($resources['result']['resources'])
        ->toHaveCount(1)
        ->and($templates['result']['resourceTemplates'])
        ->toHaveCount(1)
        ->and($firstContent['text'])
        ->toContain('library://profiles/42');
});
