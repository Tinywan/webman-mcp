<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

#[AsCommand('make:mcp-server', 'Generate a strict MCP Server scaffold')]
final class MakeMcpServerCommand extends Command
{
    public function __construct(
        private readonly CodeGenerator $generator = new CodeGenerator(),
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Server class name.');
        $this->addOption('path', null, InputOption::VALUE_REQUIRED, 'Target Webman project root.', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $path = $this->generator->server((string) $input->getOption('path'), (string) $input->getArgument('name'));
            $output->writeln("Created {$path}");

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return self::FAILURE;
        }
    }
}
