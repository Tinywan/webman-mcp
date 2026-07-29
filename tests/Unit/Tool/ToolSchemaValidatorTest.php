<?php

declare(strict_types=1);

use Pest\PendingCalls\TestCall;
use Tinywan\Mcp\Tool\Schema\SchemaValidationException;
use Tinywan\Mcp\Tool\Schema\ToolSchemaValidator;
use Tinywan\Mcp\Tool\ToolDefinition;

it('accepts local refs and validates input and output values', function (): void {
    $definition = new ToolDefinition(
        'local-ref',
        'Use a local definition.',
        [
            'type' => 'object',
            '$defs' => ['name' => ['type' => 'string', 'minLength' => 1]],
            'properties' => ['name' => ['$ref' => '#/$defs/name']],
            'required' => ['name'],
        ],
        ['type' => 'array', 'items' => ['type' => 'integer']],
    );
    $validator = new ToolSchemaValidator();

    $validator->assertDefinition($definition);

    expect($validator->validateArguments($definition, ['name' => 'Ada']))
        ->toBeTrue()
        ->and($validator->validateArguments($definition, ['name' => '']))
        ->toBeFalse()
        ->and($validator->validateOutput($definition, [1, 2]))
        ->toBeTrue()
        ->and($validator->validateOutput($definition, ['wrong']))
        ->toBeFalse();
});

/** @var TestCall $externalReferences */
$externalReferences = it('rejects every external schema reference without resolving it', function (string $reference): void {
    $definition = new ToolDefinition('external', 'External ref.', [
        'type' => 'object',
        'properties' => ['value' => ['$ref' => $reference]],
    ]);

    expect(fn() => (new ToolSchemaValidator())->assertDefinition($definition))
        ->toThrow(SchemaValidationException::class, 'non-local $ref');
});
assert($externalReferences instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$externalReferences->with([
    ['https://example.com/schema.json'],
    ['file:///tmp/schema.json'],
    ['../schema.json'],
]);
unset($externalReferences);

it('collects valid custom Header annotations', function (): void {
    $definition = new ToolDefinition('headers', 'Header annotations.', [
        'type' => 'object',
        'properties' => [
            'region' => ['type' => 'string', 'x-mcp-header' => 'Region'],
            'nested' => [
                'type' => 'object',
                'properties' => [
                    'enabled' => ['type' => 'boolean', 'x-mcp-header' => 'Enabled'],
                ],
            ],
        ],
    ]);

    $headers = (new ToolSchemaValidator())->headerAnnotations($definition);

    expect($headers)
        ->toHaveCount(2)
        ->and($headers[0]->headerName())
        ->toBe('Mcp-Param-Region')
        ->and($headers[1]->propertyPath)
        ->toBe(['nested', 'enabled']);
});

/** @var TestCall $invalidHeaderAnnotations */
$invalidHeaderAnnotations = it('rejects invalid custom Header annotations', function (array $schema): void {
    /** @var array<string, mixed> $schema */
    expect(fn() => (new ToolSchemaValidator())->assertDefinition(new ToolDefinition('invalid-header', '', $schema)))
        ->toThrow(SchemaValidationException::class);
});
assert($invalidHeaderAnnotations instanceof TestCall, description: 'Pest must return a dataset-capable TestCall.');
$invalidHeaderAnnotations->with([
    'unsafe token' => [[
        'type' => 'object',
        'properties' => ['value' => ['type' => 'string', 'x-mcp-header' => "Bad\nHeader"]],
    ]],
    'number type' => [[
        'type' => 'object',
        'properties' => ['value' => ['type' => 'number', 'x-mcp-header' => 'Value']],
    ]],
    'duplicate case-insensitive name' => [[
        'type' => 'object',
        'properties' => [
            'one' => ['type' => 'string', 'x-mcp-header' => 'Region'],
            'two' => ['type' => 'string', 'x-mcp-header' => 'region'],
        ],
    ]],
    'annotation under items' => [[
        'type' => 'object',
        'properties' => [
            'values' => [
                'type' => 'array',
                'items' => ['type' => 'string', 'x-mcp-header' => 'Value'],
            ],
        ],
    ]],
]);
unset($invalidHeaderAnnotations);
