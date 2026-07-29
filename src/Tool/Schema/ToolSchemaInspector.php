<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool\Schema;

use Tinywan\Mcp\Tool\ToolDefinition;

final class ToolSchemaInspector
{
    private const DIALECT = 'https://json-schema.org/draft/2020-12/schema';

    public function __construct(
        private readonly HeaderAnnotationCollector $headerCollector = new HeaderAnnotationCollector(),
    ) {}

    public function inspect(ToolDefinition $definition): void
    {
        if (($definition->inputSchema['type'] ?? null) !== 'object') {
            throw new SchemaValidationException("Tool '{$definition->name}' inputSchema must have object type.");
        }

        $this->assertSchema($definition->inputSchema, "Tool '{$definition->name}' inputSchema");
        $this->headerAnnotations($definition);

        if ($definition->outputSchema !== null) {
            $this->assertSchema($definition->outputSchema, "Tool '{$definition->name}' outputSchema");
        }
    }

    /**
     * @return list<HeaderAnnotation>
     */
    public function headerAnnotations(ToolDefinition $definition): array
    {
        return $this->headerCollector->collect($definition->inputSchema);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function assertSchema(array $schema, string $label): void
    {
        $dialect = $this->schemaDialect($schema);
        if ($dialect !== self::DIALECT) {
            throw new SchemaValidationException("{$label} must use JSON Schema 2020-12.");
        }

        $this->assertLocalReferences($schema, $label);
    }

    /**
     * @param array<array-key, mixed> $schema
     */
    private function assertLocalReferences(array $schema, string $label): void
    {
        foreach (array_keys($schema) as $key) {
            $this->assertLocalReferenceValue($key, $schema[$key], $label);
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function schemaDialect(array $schema): string
    {
        if (!array_key_exists('$schema', $schema)) {
            return self::DIALECT;
        }

        if (!is_string($schema['$schema'])) {
            throw new SchemaValidationException('JSON Schema dialect must be a string.');
        }

        return $schema['$schema'];
    }

    private function assertLocalReferenceValue(int|string $key, mixed $value, string $label): void
    {
        if ($key === '$ref' && (!is_string($value) || !str_starts_with($value, '#'))) {
            throw new SchemaValidationException("{$label} contains a non-local \$ref.");
        }

        if (is_array($value)) {
            $this->assertLocalReferences($value, $label);
        }
    }
}
