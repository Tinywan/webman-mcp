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
use Tinywan\Mcp\Security\StaticBearerAuthenticator;
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
        } catch (Throwable) {
            $io->error('MCP inspection failed: invalid configuration.');

            return self::FAILURE;
        }

        $tools = 0;
        $bearer = 0;
        $rateLimited = 0;
        $concurrencyLimited = 0;
        $idempotent = 0;
        foreach ($registry->servers() as $server) {
            $tools += count($server->tools());
            $bearer += $server->authenticator instanceof StaticBearerAuthenticator ? 1 : 0;
            $rateLimited += $server->options->governance->rateLimiter === null ? 0 : 1;
            $concurrencyLimited += $server->options->governance->concurrencyLimiter === null ? 0 : 1;
            $idempotent += $server->options->governance->idempotentMethods === [] ? 0 : 1;
        }

        $io->success(sprintf(
            'MCP configuration is valid. Servers: %d; Tools: %d; Bearer: %d; Rate: %d; Concurrency: %d; Idempotency: %d.',
            count($registry->servers()),
            $tools,
            $bearer,
            $rateLimited,
            $concurrencyLimited,
            $idempotent,
        ));

        return self::SUCCESS;
    }
}
