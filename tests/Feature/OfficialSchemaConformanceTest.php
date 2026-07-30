<?php

declare(strict_types=1);

use Opis\JsonSchema\Validator;
use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Examples\Calculator\CalculatorServer;
use Tinywan\Mcp\Registry\ServerRegistry;
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
