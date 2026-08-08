<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Webman;

use RuntimeException;
use Tinywan\Mcp\Command\ProjectWriter;

final readonly class ConfigPublisher
{
    private const FILES = ['app.php', 'command.php', 'servers.php', 'route.php', 'production.php'];

    public function __construct(
        private ProjectWriter $writer = new ProjectWriter(),
        private string $packageRoot = __DIR__ . '/../..',
    ) {}

    /**
     * @return array<string, bool>
     */
    public function publish(string $projectRoot): array
    {
        $root = rtrim($projectRoot, characters: '/\\');
        $results = [];
        foreach (self::FILES as $file) {
            $relative = "config/plugin/tinywan/webman-mcp/{$file}";
            $contents = file_get_contents($this->packageRoot . "/src/{$relative}");
            if ($contents === false) {
                throw new RuntimeException("Missing package asset: {$relative}");
            }

            $results[$relative] = $this->writer->write("{$root}/{$relative}", $contents);
        }

        return $results;
    }
}
