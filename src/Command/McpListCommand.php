<?php

declare(strict_types=1);

namespace Tinywan\Mcp\Command;

use Closure;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Webman\RegistryProvider;

#[AsCommand('mcp:list', 'List configured MCP Servers and Tools')]
final class McpListCommand extends Command
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
            $output->writeln("<error>{$exception->getMessage()}</error>");

            return self::FAILURE;
        }

        $servers = $registry->servers();
        if ($servers === []) {
            $output->writeln('No MCP Servers configured.');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($servers as $server) {
            $tools = array_map(static fn(RegisteredTool $tool): string => $tool->definition->name, $server->tools());
            $rows[] = [$server->id, $server->path, implode(PHP_EOL, $tools)];
        }

        (new Table($output))
            ->setHeaders(['Server', 'Endpoint', 'Tools'])
            ->setRows($rows)
            ->render();

        return self::SUCCESS;
    }
}
