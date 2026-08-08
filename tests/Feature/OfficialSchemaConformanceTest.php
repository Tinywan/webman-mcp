<?php

declare(strict_types=1);

use Opis\JsonSchema\Validator;
use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Examples\Assistant\AssistantServer;
use Tinywan\Mcp\Examples\Calculator\CalculatorServer;
use Tinywan\Mcp\Examples\Library\LibraryServer;
use Tinywan\Mcp\Examples\Status\StatusServer;
use Tinywan\Mcp\Prompt\FactoryPromptResolver;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Resource\FactoryResourceResolver;
use Tinywan\Mcp\Tool\FactoryToolResolver;
use Tinywan\Mcp\Transport\HttpRequestContext;
use Tinywan\Mcp\Transport\StreamableHttpTransport;

const OFFICIAL_SCHEMA_SIZE = 181_474;

const OFFICIAL_SCHEMA_SHA256 = 'ef70b61f99b6d2e5e3b46863822eab08dff6a45bedc7a08914e0e5b133f40203';

function official_schema_path(): string
{
    return dirname(__DIR__, levels: 2) . '/resources/schema/2026-07-28-schema.json';
}

function official_fixture_path(string $name): string
{
    return dirname(__DIR__) . "/Fixtures/Protocol/{$name}.json";
}

function official_object(mixed $value, string $description): stdClass
{
    assert($value instanceof stdClass, description: $description);

    return $value;
}

function official_json_object(string $path): stdClass
{
    $contents = file_get_contents($path);
    assert(is_string($contents), description: "Unable to read JSON fixture: {$path}");

    return official_object(
        json_decode($contents, associative: false, depth: 512, flags: JSON_THROW_ON_ERROR),
        "JSON fixture must be an object: {$path}",
    );
}

function official_definition_schema(string $definition): stdClass
{
    $schema = official_json_object(official_schema_path());
    $schema->{'$ref'} = '#/$defs/' . $definition;

    return $schema;
}

function official_validates(stdClass $value, string $definition): bool
{
    return (new Validator())
        ->validate($value, official_definition_schema($definition))
        ->isValid();
}

/**
 * @return array<string, string>
 */
function official_headers(stdClass $value): array
{
    /** @var array<string, mixed> $properties */
    $properties = get_object_vars($value);
    $headers = [];
    foreach (array_keys($properties) as $name) {
        assert(is_string($properties[$name]), description: "Fixture Header {$name} must be a string.");
        $headers[$name] = $properties[$name];
    }

    return $headers;
}

it('pins the complete official MCP schema byte-for-byte', function (): void {
    $path = official_schema_path();

    expect(filesize($path))
        ->toBe(OFFICIAL_SCHEMA_SIZE)
        ->and(hash_file('sha256', $path))
        ->toBe(OFFICIAL_SCHEMA_SHA256)
        ->and(official_json_object($path)->{'$schema'} ?? null)
        ->toBe('https://json-schema.org/draft/2020-12/schema');
});

/** @var TestCall $officialConformance */
$officialConformance = it('conforms to the official schema with the Calculator transport', function (
    string $fixtureName,
    string $requestDefinition,
    string $resultDefinition,
): void {
    $fixture = official_json_object(official_fixture_path($fixtureName));
    $headers = official_headers(official_object(
        $fixture->headers ?? null,
        description: 'Fixture Headers are required.',
    ));
    $requestObject = official_object($fixture->body ?? null, description: 'Fixture Body is required.');

    expect(official_validates($requestObject, $requestDefinition))->toBeTrue();

    $transport = new StreamableHttpTransport(
        new ServerRegistry([CalculatorServer::definition()]),
        new FactoryToolResolver(),
    );
    $body = json_encode($requestObject, JSON_THROW_ON_ERROR);
    $response = $transport->handle(new HttpRequestContext('POST', '/mcp/calculator', $headers, $body));
    $responseObject = official_object(
        json_decode($response->body, associative: false, depth: 512, flags: JSON_THROW_ON_ERROR),
        description: 'Protocol response must be a JSON object.',
    );

    expect($response->status)->toBe(200)->and(official_validates($responseObject, $resultDefinition))->toBeTrue();

    if ($fixtureName === 'codex-tools-call') {
        expect($responseObject->result->structuredContent->value ?? null)->toBe(13);
    }
});
assert($officialConformance instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$officialConformance->with([
    'server discovery' => ['server-discover', 'DiscoverRequest', 'DiscoverResultResponse'],
    'Tool listing' => ['tools-list', 'ListToolsRequest', 'ListToolsResultResponse'],
    'Codex Tool call' => ['codex-tools-call', 'CallToolRequest', 'CallToolResultResponse'],
]);
unset($officialConformance);

/** @var TestCall $officialResourceConformance */
$officialResourceConformance = it('conforms to the official schema with the Library Resource transport', function (
    string $fixtureName,
    string $requestDefinition,
    string $resultDefinition,
): void {
    $fixture = official_json_object(official_fixture_path($fixtureName));
    $headers = official_headers(official_object(
        $fixture->headers ?? null,
        description: 'Fixture Headers are required.',
    ));
    $requestObject = official_object($fixture->body ?? null, description: 'Fixture Body is required.');
    expect(official_validates($requestObject, $requestDefinition))->toBeTrue();

    $transport = new StreamableHttpTransport(
        new ServerRegistry([LibraryServer::definition()]),
        new FactoryToolResolver(),
        new FactoryResourceResolver(),
    );
    $response = $transport->handle(
        new HttpRequestContext('POST', '/mcp/library', $headers, json_encode($requestObject, JSON_THROW_ON_ERROR)),
    );
    $responseObject = official_object(
        json_decode($response->body, associative: false, depth: 512, flags: JSON_THROW_ON_ERROR),
        description: 'Resource response must be a JSON object.',
    );

    expect($response->status)->toBe(200)->and(official_validates($responseObject, $resultDefinition))->toBeTrue();
});
assert($officialResourceConformance instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$officialResourceConformance->with([
    'Resource listing' => ['resources-list', 'ListResourcesRequest', 'ListResourcesResultResponse'],
    'Resource Template listing' => [
        'resource-templates-list',
        'ListResourceTemplatesRequest',
        'ListResourceTemplatesResultResponse',
    ],
    'Resource read' => ['resource-read', 'ReadResourceRequest', 'ReadResourceResultResponse'],
]);
unset($officialResourceConformance);

/** @var TestCall $officialPromptConformance */
$officialPromptConformance = it('conforms to the official schema with the Assistant Prompt transport', function (
    string $fixtureName,
    string $requestDefinition,
    string $resultDefinition,
): void {
    $fixture = official_json_object(official_fixture_path($fixtureName));
    $headers = official_headers(official_object($fixture->headers ?? null, description: 'Fixture Headers required.'));
    $requestObject = official_object($fixture->body ?? null, description: 'Fixture Body required.');
    expect(official_validates($requestObject, $requestDefinition))->toBeTrue();

    $transport = new StreamableHttpTransport(
        new ServerRegistry([AssistantServer::definition()]),
        new FactoryToolResolver(),
        new FactoryResourceResolver(),
        new FactoryPromptResolver(),
    );
    $response = $transport->handle(
        new HttpRequestContext('POST', '/mcp/assistant', $headers, json_encode($requestObject, JSON_THROW_ON_ERROR)),
    );
    $responseObject = official_object(
        json_decode($response->body, associative: false, depth: 512, flags: JSON_THROW_ON_ERROR),
        description: 'Prompt response must be a JSON object.',
    );

    expect($response->status)->toBe(200)->and(official_validates($responseObject, $resultDefinition))->toBeTrue();
});
assert($officialPromptConformance instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$officialPromptConformance->with([
    'Prompt listing' => ['prompts-list', 'ListPromptsRequest', 'ListPromptsResultResponse'],
    'Prompt get' => ['prompt-get', 'GetPromptRequest', 'GetPromptResultResponse'],
    'Completion' => ['completion-complete', 'CompleteRequest', 'CompleteResultResponse'],
]);
unset($officialPromptConformance);

it('conforms to official Subscription and notification schemas over an event stream', function (): void {
    $fixture = official_json_object(official_fixture_path('subscriptions-listen'));
    $requestObject = official_object($fixture->body ?? null, description: 'Subscription fixture Body required.');
    expect(official_validates($requestObject, definition: 'SubscriptionsListenRequest'))->toBeTrue();

    $transport = new StreamableHttpTransport(
        new ServerRegistry([StatusServer::definition()]),
        new FactoryToolResolver(),
    );
    $response = $transport->handle(
        new HttpRequestContext(
            'POST',
            '/mcp/status',
            official_headers(official_object($fixture->headers ?? null, description: 'Subscription Headers required.')),
            json_encode($requestObject, JSON_THROW_ON_ERROR),
        ),
    );
    $matches = [];
    preg_match_all('/^data: (.+)$/m', $response->body, $matches);
    $definitions = [
        'SubscriptionsAcknowledgedNotification',
        'ToolListChangedNotification',
        'ResourceUpdatedNotification',
        'SubscriptionsListenResultResponse',
    ];
    expect($response->status)
        ->toBe(200)
        ->and($response->headers['Content-Type'] ?? null)
        ->toBe('text/event-stream')
        ->and($matches[1])
        ->toHaveCount(4)
        ->and(array_change_key_case($response->headers))
        ->not->toHaveKeys(['mcp-session-id', 'last-event-id']);

    foreach (array_keys($definitions) as $index) {
        $event = official_object(
            json_decode($matches[1][$index], associative: false, flags: JSON_THROW_ON_ERROR),
            description: 'SSE data must contain a JSON object.',
        );
        expect(official_validates($event, $definitions[$index]))->toBeTrue();
    }
});
