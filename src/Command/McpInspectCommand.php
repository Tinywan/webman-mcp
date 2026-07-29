<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Command;

use Closure;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        try {
            $registry = ($this->registryLoader)();
        } catch (Throwable $exception) {
            $output->writeln("<error>MCP inspection failed: {$exception->getMessage()}</error>");

            return self::FAILURE;
        }

        $tools = 0;
        foreach ($registry->servers() as $server) {
            $tools += count($server->tools());
        }

        $output->writeln(sprintf(
            'MCP configuration valid: %d Server(s), %d Tool(s).',
            count($registry->servers()),
            $tools,
        ));

        return self::SUCCESS;
    }
}
