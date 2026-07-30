<?php

declare(strict_types=1);

use Tinywan\Mcp\Command\MakeMcpServerCommand;
use Tinywan\Mcp\Command\MakeMcpToolCommand;
use Tinywan\Mcp\Command\McpInspectCommand;
use Tinywan\Mcp\Command\McpInstallCommand;
use Tinywan\Mcp\Command\McpListCommand;

return [
    McpInstallCommand::class,
    MakeMcpServerCommand::class,
    MakeMcpToolCommand::class,
    McpListCommand::class,
    McpInspectCommand::class,
];
