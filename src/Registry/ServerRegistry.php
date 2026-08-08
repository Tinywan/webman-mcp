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
            $this->validateResources($server);
            $this->validatePrompts($server);
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

    private function validateResources(ServerDefinition $server): void
    {
        $uris = [];
        foreach ($server->resources() as $resource) {
            $uri = $resource->definition->uri;
            if (array_key_exists($uri, $uris)) {
                throw new RegistryException("Duplicate Resource URI '{$uri}' in Server '{$server->id}'.");
            }
            $uris[$uri] = true;
        }

        $templates = [];
        foreach ($server->resourceTemplates() as $template) {
            $uriTemplate = $template->definition->uriTemplate;
            if (array_key_exists($uriTemplate, $templates)) {
                throw new RegistryException("Duplicate Resource Template '{$uriTemplate}' in Server '{$server->id}'.");
            }
            $templates[$uriTemplate] = true;
        }
    }

    private function validatePrompts(ServerDefinition $server): void
    {
        $names = [];
        foreach ($server->prompts() as $prompt) {
            if (array_key_exists($prompt->definition->name, $names)) {
                throw new RegistryException(
                    "Duplicate Prompt name '{$prompt->definition->name}' in Server '{$server->id}'.",
                );
            }
            $names[$prompt->definition->name] = true;
        }

        $references = [];
        foreach ($server->completions() as $completion) {
            $key = $completion->reference->key();
            if (array_key_exists($key, $references)) {
                throw new RegistryException("Duplicate Completion reference '{$key}' in Server '{$server->id}'.");
            }
            $references[$key] = true;
        }
    }
}
