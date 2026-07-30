<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Command;

use Closure;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Webman\RegistryProvider;

#[AsCommand('mcp:inspect', 'Validate MCP configuration and Schemas offline')]
final class McpInspectCommand extends Command
{
    /** @var Closure(): ServerRegistry */
    private readonly Closure $registryLoader;

    /**
     * @param null|Closure(): ServerRegistry $registryLoader
     */
    public function __construct(?Closure $registryLoader = null)
    {
        $this->registryLoader = $registryLoader ?? RegistryProvider::load(...);
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $registry = ($this->registryLoader)();
        } catch (Throwable $exception) {
            $io->error("MCP inspection failed: {$exception->getMessage()}");

            return self::FAILURE;
        }

        $tools = 0;
        foreach ($registry->servers() as $server) {
            $tools += count($server->tools());
        }

        $io->success(sprintf(
            'MCP configuration is valid. Servers: %d; Tools: %d.',
            count($registry->servers()),
            $tools,
        ));

        return self::SUCCESS;
    }
}
