<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Command;

use InvalidArgumentException;
use RuntimeException;

final readonly class CodeGenerator
{
    public function __construct(
        private ProjectWriter $writer = new ProjectWriter(),
        private string $packageRoot = __DIR__ . '/../..',
    ) {}

    public function tool(string $projectRoot, string $name): string
    {
        $base = $this->classBase($name, 'Tool');
        $class = "{$base}Tool";

        return $this->generate($projectRoot, $class, 'McpTool.stub', [
            '{{ namespace }}' => 'app\\mcp',
            '{{ class }}' => $class,
            '{{ id }}' => $this->identifier($base),
            '{{ title }}' => "{$base} Tool",
        ]);
    }

    public function server(string $projectRoot, string $name): string
    {
        $base = $this->classBase($name, 'Server');
        $class = "{$base}Server";
        $id = $this->identifier($base);

        return $this->generate($projectRoot, $class, 'McpServer.stub', [
            '{{ namespace }}' => 'app\\mcp',
            '{{ class }}' => $class,
            '{{ id }}' => $id,
            '{{ path }}' => "/mcp/{$id}",
            '{{ title }}' => "{$base} Server",
        ]);
    }

    /**
     * @param array<string, string> $replacements
     */
    private function generate(string $projectRoot, string $class, string $stub, array $replacements): string
    {
        $template = file_get_contents($this->packageRoot . "/resources/stubs/{$stub}");
        if ($template === false) {
            throw new RuntimeException("Unable to read generator stub: {$stub}");
        }

        $target = rtrim($projectRoot, characters: '/\\') . "/app/mcp/{$class}.php";
        if (!$this->writer->write($target, strtr($template, $replacements))) {
            throw new RuntimeException("Target already exists: {$target}");
        }

        return $target;
    }

    private function classBase(string $name, string $suffix): string
    {
        $name = trim($name);
        if (preg_match('/^[A-Za-z][A-Za-z0-9_]*$/D', $name) !== 1) {
            throw new InvalidArgumentException(
                'Name must start with a letter and contain only letters, digits, or underscores.',
            );
        }

        $words = str_replace(search: '_', replace: ' ', subject: $name);
        $class = str_replace(search: ' ', replace: '', subject: ucwords($words));

        return str_ends_with($class, $suffix) ? substr($class, offset: 0, length: -strlen($suffix)) : $class;
    }

    private function identifier(string $class): string
    {
        $identifier = preg_replace(pattern: '/(?<!^)[A-Z]/', replacement: '-$0', subject: $class);
        if ($identifier === null) {
            throw new RuntimeException('Unable to derive an MCP identifier.');
        }

        return strtolower($identifier);
    }
}
