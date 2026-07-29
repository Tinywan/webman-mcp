<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Tool\Schema;

use JsonException;
use Opis\JsonSchema\Validator;
use Throwable;
use Tinywan\Mcp\Tool\ToolDefinition;

final readonly class ToolSchemaValidator
{
    public function __construct(
        private Validator $validator = new Validator(),
        private ToolSchemaInspector $inspector = new ToolSchemaInspector(),
    ) {}

    public function assertDefinition(ToolDefinition $definition): void
    {
        $this->inspector->inspect($definition);

        try {
            $this->validator->validate(null, $this->schemaObject($definition->inputSchema));
            if ($definition->outputSchema !== null) {
                $this->validator->validate(null, $this->schemaObject($definition->outputSchema));
            }
        } catch (Throwable) {
            throw new SchemaValidationException("Tool '{$definition->name}' contains an invalid JSON Schema.");
        }
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function validateArguments(ToolDefinition $definition, array $arguments): bool
    {
        return $this->validate((object) $arguments, $definition->inputSchema);
    }

    public function validateOutput(ToolDefinition $definition, mixed $output): bool
    {
        if ($definition->outputSchema === null) {
            return true;
        }

        return $this->validate($output, $definition->outputSchema);
    }

    /**
     * @return list<HeaderAnnotation>
     */
    public function headerAnnotations(ToolDefinition $definition): array
    {
        return $this->inspector->headerAnnotations($definition);
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function validate(mixed $data, array $schema): bool
    {
        try {
            $normalized = $this->normalizeJsonValue($data);

            return $this->validator->validate($normalized, $this->schemaObject($schema))->isValid();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function schemaObject(array $schema): object
    {
        $value = $this->normalizeJsonValue($schema);
        if (!is_object($value)) {
            throw new SchemaValidationException('A JSON Schema must be an object.');
        }

        return $value;
    }

    /**
     * @return null|bool|int|float|string|array<array-key, mixed>|object
     */
    private function normalizeJsonValue(mixed $value): bool|int|float|string|array|object|null
    {
        try {
            $encoded = json_encode($value, JSON_THROW_ON_ERROR);

            return $this->decodedJsonValue(json_decode(
                json: $encoded,
                associative: false,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException) {
            throw new SchemaValidationException('Value is not JSON serializable.');
        }
    }

    /**
     * @return null|bool|int|float|string|array<array-key, mixed>|object
     */
    private function decodedJsonValue(mixed $value): bool|int|float|string|array|object|null
    {
        if ($value === null || is_scalar($value) || is_array($value) || is_object($value)) {
            return $value;
        }

        throw new SchemaValidationException('Value is not JSON serializable.');
    }
}
