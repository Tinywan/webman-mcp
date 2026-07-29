<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mcp:install', 'Publish Webman MCP configuration without overwriting files')]
final class McpInstallCommand extends Command
{
    public function __construct(
        private readonly ProjectWriter $writer = new ProjectWriter(),
        private readonly string $packageRoot = __DIR__ . '/../..',
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('path', null, InputOption::VALUE_REQUIRED, 'Target Webman project root.', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $root = rtrim((string) $input->getOption('path'), characters: '/\\');
        $conflicts = 0;
        foreach (['app.php', 'servers.php', 'route.php'] as $file) {
            $relative = "config/plugin/tinywan/webman-mcp/{$file}";
            $contents = file_get_contents($this->packageRoot . "/{$relative}");
            if ($contents === false) {
                $output->writeln("<error>Missing package asset: {$relative}</error>");

                return self::FAILURE;
            }

            if (!$this->writer->write("{$root}/{$relative}", $contents)) {
                $output->writeln("<comment>Conflict, preserved existing file: {$relative}</comment>");
                $conflicts++;
                continue;
            }

            $output->writeln("Published {$relative}");
        }

        return $conflicts === 0 ? self::SUCCESS : self::FAILURE;
    }
}
