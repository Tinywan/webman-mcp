<?php

declare(strict_types=1);

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tinywan\Mcp\Command\MakeMcpServerCommand;
use Tinywan\Mcp\Command\MakeMcpToolCommand;
use Tinywan\Mcp\Command\McpInspectCommand;
use Tinywan\Mcp\Command\McpInstallCommand;
use Tinywan\Mcp\Command\McpListCommand;
use Tinywan\Mcp\Install;
use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\RegistryException;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Registry\ServerRegistry;
use Tinywan\Mcp\Security\AllowAllAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;
use Tinywan\Mcp\Tests\Fixtures\EchoTool;
use Tinywan\Mcp\Webman\McpBootstrap;
use Tinywan\Mcp\Webman\RegistryProvider;
use Webman\Container;

function cli_temp_dir(): string
{
    $path = sys_get_temp_dir() . '/webman-mcp-' . bin2hex(random_bytes(6));
    if (!mkdir($path, permissions: 0o777, recursive: true) && !is_dir($path)) {
        throw new RuntimeException("Unable to create test directory: {$path}");
    }

    return $path;
}

function remove_test_tree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $item) {
        if (!$item instanceof SplFileInfo) {
            continue;
        }

        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
    rmdir($path);
}

function cli_server(string $id, string $path): ServerDefinition
{
    $echo = new EchoTool();

    return new ServerDefinition(
        $id,
        $path,
        new ServerIdentity("{$id} server", '0.1.0'),
        [new RegisteredTool($echo->definition(), EchoTool::class)],
        new AllowAnonymousAuthenticator(),
        new AllowAllAuthorizer(),
    );
}

it('publishes configuration once and preserves every existing target', function (): void {
    $root = cli_temp_dir();
    try {
        $first = new CommandTester(new McpInstallCommand());
        expect($first->execute(['--path' => $root]))->toBe(Command::SUCCESS);

        $app = $root . '/config/plugin/tinywan/webman-mcp/app.php';
        $command = $root . '/config/plugin/tinywan/webman-mcp/command.php';
        $servers = $root . '/config/plugin/tinywan/webman-mcp/servers.php';
        $route = $root . '/config/plugin/tinywan/webman-mcp/route.php';
        $production = $root . '/config/plugin/tinywan/webman-mcp/production.php';
        expect($app)
            ->toBeFile()
            ->and($command)
            ->toBeFile()
            ->and($servers)
            ->toBeFile()
            ->and($route)
            ->toBeFile()
            ->and($production)
            ->toBeFile();

        $custom = "<?php\n\n// application-owned\n";
        file_put_contents($app, $custom, LOCK_EX);
        $second = new CommandTester(new McpInstallCommand());

        expect($second->execute(['--path' => $root]))
            ->toBe(Command::FAILURE)
            ->and(file_get_contents($app))
            ->toBe($custom)
            ->and($second->getDisplay())
            ->toContain('preserved existing file');
    } finally {
        remove_test_tree($root);
    }
});

it('generates strict Server and Tool scaffolds and refuses overwrite', function (): void {
    $root = cli_temp_dir();
    try {
        $tool = new CommandTester(new MakeMcpToolCommand());
        $server = new CommandTester(new MakeMcpServerCommand());
        expect($tool->execute(['name' => 'Weather', '--path' => $root]))
            ->toBe(Command::SUCCESS)
            ->and($server->execute(['name' => 'Operations', '--path' => $root]))
            ->toBe(Command::SUCCESS);

        $toolPath = $root . '/app/mcp/WeatherTool.php';
        $serverPath = $root . '/app/mcp/OperationsServer.php';
        $toolBytes = file_get_contents($toolPath);
        expect($toolBytes)
            ->toContain('declare(strict_types=1);')
            ->toContain('implements ToolInterface')
            ->and(file_get_contents($serverPath))
            ->toContain('declare(strict_types=1);')
            ->toContain(
                "return new ServerDefinition('operations', '/mcp/operations', new ServerIdentity('Operations Server', '0.1.0'), []);",
            );

        expect($tool->execute(['name' => 'Weather', '--path' => $root]))
            ->toBe(Command::FAILURE)
            ->and(file_get_contents($toolPath))
            ->toBe($toolBytes);
    } finally {
        remove_test_tree($root);
    }
});

it('lists normalized topology without authentication details', function (): void {
    $registry = new ServerRegistry([cli_server('primary', path: 'mcp/')]);
    $command = new CommandTester(new McpListCommand(static fn(): ServerRegistry => $registry));
    $status = $command->execute([]);
    $display = $command->getDisplay();

    expect($status)
        ->toBe(Command::SUCCESS)
        ->and($display)
        ->toContain('Server')
        ->toContain('Endpoint')
        ->toContain('Tools')
        ->toContain('primary')
        ->toContain('/mcp')
        ->toContain('echo')
        ->and(str_contains($display, 'anonymous'))
        ->toBeFalse()
        ->and(str_contains($display, 'authenticator'))
        ->toBeFalse()
        ->and(str_contains($display, 'attributes'))
        ->toBeFalse();
});

it('inspects valid configuration and returns nonzero diagnostics for invalid configuration', function (): void {
    $valid = new CommandTester(new McpInspectCommand(static fn(): ServerRegistry => new ServerRegistry([cli_server(
        'valid',
        path: '/valid',
    )])));
    $invalid = new CommandTester(new McpInspectCommand(static function (): ServerRegistry {
        throw new RegistryException('Duplicate Server path: /same');
    }));

    expect($valid->execute([]))
        ->toBe(Command::SUCCESS)
        ->and($valid->getDisplay())
        ->toContain('[OK]')
        ->toContain('MCP configuration is valid')
        ->toContain('Servers: 1')
        ->toContain('Tools: 1')
        ->toContain('Bearer: 0')
        ->and($invalid->execute([]))
        ->toBe(Command::FAILURE)
        ->and($invalid->getDisplay())
        ->toContain('[ERROR]')
        ->toContain('MCP inspection failed')
        ->toContain('invalid configuration');
    expect($invalid->getDisplay())->not->toContain('Duplicate Server path');
});

it('validates configured definitions and builds one callback per Server path', function (): void {
    $registry = RegistryProvider::fromConfig([
        'servers' => [cli_server('one', path: '/one'), cli_server('two', path: '/two')],
    ]);
    $callbacks = McpBootstrap::routeCallbacks($registry, new Container());

    expect(array_keys($callbacks))->toBe(['/one', '/two']);
    expect(fn(): ServerRegistry => RegistryProvider::fromConfig(['bad']))
        ->toThrow(RegistryException::class, 'must be a ServerDefinition');
});

it('ships command registration and deny-all empty Server defaults', function (): void {
    $configRoot = dirname(__DIR__, levels: 3) . '/src/config/plugin/tinywan/webman-mcp';
    $app = required_config(require "{$configRoot}/app.php");
    $commands = required_command_config(require "{$configRoot}/command.php");
    $servers = required_config(require "{$configRoot}/servers.php");

    expect($app['enable'])
        ->toBeTrue()
        ->and($commands)
        ->toHaveCount(5)
        ->and($servers)
        ->toBe(['servers' => []])
        ->and(Install::WEBMAN_PLUGIN)
        ->toBeTrue()
        ->and(dirname(__DIR__, levels: 3) . '/config/app.php')
        ->not->toBeFile();
});

/**
 * @return array<string, mixed>
 */
function required_config(mixed $config): array
{
    assert(is_array($config) && !array_is_list($config), description: 'Plugin configuration must be an object array.');

    /** @var array<string, mixed> $config */
    return $config;
}

/**
 * @return list<class-string>
 */
function required_command_config(mixed $config): array
{
    assert(is_array($config) && array_is_list($config), description: 'Command configuration must be a list.');

    /** @var list<class-string> $config */
    return $config;
}
