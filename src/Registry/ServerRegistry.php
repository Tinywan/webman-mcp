<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Registry;

use Tinywan\Mcp\Tool\Schema\ToolSchemaValidator;

final readonly class ServerRegistry
{
    /** @var array<string, ServerDefinition> */
    private array $byId;

    /** @var array<string, ServerDefinition> */
    private array $byPath;

    /**
     * @param iterable<ServerDefinition> $servers
     */
    public function __construct(iterable $servers, ?ToolSchemaValidator $schemaValidator = null)
    {
        $byId = [];
        $byPath = [];
        $validator = $schemaValidator ?? new ToolSchemaValidator();

        foreach ($servers as $server) {
            if (array_key_exists($server->id, $byId)) {
                throw new RegistryException("Duplicate Server ID: {$server->id}");
            }

            if (array_key_exists($server->path, $byPath)) {
                throw new RegistryException("Duplicate Server path: {$server->path}");
            }

            $this->validateTools($server, $validator);
            $byId[$server->id] = $server;
            $byPath[$server->path] = $server;
        }

        $this->byId = $byId;
        $this->byPath = $byPath;
    }

    public function byId(string $id): ?ServerDefinition
    {
        return $this->byId[$id] ?? null;
    }

    public function byPath(string $path): ?ServerDefinition
    {
        $normalized = '/' . trim($path, characters: '/');
        $normalized = $normalized === '/' ? '/' : rtrim($normalized, characters: '/');

        return $this->byPath[$normalized] ?? null;
    }

    /**
     * @return list<ServerDefinition>
     */
    public function servers(): array
    {
        return array_values($this->byId);
    }

    private function validateTools(ServerDefinition $server, ToolSchemaValidator $validator): void
    {
        $names = [];
        foreach ($server->tools() as $tool) {
            $name = $tool->definition->name;
            if (array_key_exists($name, $names)) {
                throw new RegistryException("Duplicate Tool name '{$name}' in Server '{$server->id}'.");
            }

            $validator->assertDefinition($tool->definition);
            $names[$name] = true;
        }
    }
}
