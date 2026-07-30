<?php

declare(strict_types=1);

/**
 * @return list<string>
 */
function maintained_php_files(): array
{
    $root = dirname(__DIR__);
    $files = [];

    foreach (['src', 'tests', 'examples'] as $directory) {
        $path = $root . DIRECTORY_SEPARATOR . $directory;
        if (!is_dir($path)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}
