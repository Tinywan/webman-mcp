<?php

declare(strict_types=1);

use Tinywan\Mcp\Webman\McpBootstrap;

McpBootstrap::register(config('plugin.tinywan.webman-mcp.servers', []));
