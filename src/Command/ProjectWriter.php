<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Command;

use RuntimeException;

final class ProjectWriter
{
    public function write(string $path, string $contents): bool
    {
        if (file_exists($path)) {
            return false;
        }

        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, permissions: 0o777, recursive: true) && !is_dir($directory)) {
            throw new RuntimeException("Unable to create directory: {$directory}");
        }

        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException("Unable to write file: {$path}");
        }

        return true;
    }
}
