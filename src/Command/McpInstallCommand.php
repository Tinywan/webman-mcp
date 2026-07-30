<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Command;

use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tinywan\Mcp\Webman\ConfigPublisher;

#[AsCommand('mcp:install', 'Publish Webman MCP configuration without overwriting files')]
final class McpInstallCommand extends Command
{
    public function __construct(
        private readonly ConfigPublisher $publisher = new ConfigPublisher(),
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
        try {
            $results = $this->publisher->publish($root);
        } catch (RuntimeException $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return self::FAILURE;
        }

        $conflicts = 0;
        foreach ($results as $relative => $published) {
            if (!$published) {
                $output->writeln("<comment>Conflict, preserved existing file: {$relative}</comment>");
                $conflicts++;
                continue;
            }

            $output->writeln("Published {$relative}");
        }

        return $conflicts === 0 ? self::SUCCESS : self::FAILURE;
    }
}
