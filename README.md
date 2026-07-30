# Webman MCP

`tinywan/webman-mcp` is a stateless MCP Server SDK for Webman and PHP 8.2+. Version 0.1 implements
only protocol `2026-07-28` and the `server/discover`, `tools/list`, and `tools/call` methods.

## Requirements

- PHP 8.2 or later
- Webman 2.1 or later
- JSON extension

## Install

Run the following command in a Webman project:

```bash
composer require tinywan/webman-mcp
```

Webman publishes the package configuration from `src/config/plugin/tinywan/webman-mcp` to
`config/plugin/tinywan/webman-mcp` during installation.

## Quick Start

This example creates a Calculator MCP Server and exposes a `calculate` Tool that adds two numbers.

### 1. Generate the Server and Tool

Run these commands in the Webman project root:

```bash
php webman make:mcp-server Calculator
php webman make:mcp-tool Calculator
```

They create:

```text
app/mcp/CalculatorServer.php
app/mcp/CalculatorTool.php
```

### 2. Implement the Tool

Replace `app/mcp/CalculatorTool.php` with:

```php
<?php

declare(strict_types=1);

namespace app\mcp;

use Tinywan\Mcp\Contracts\ToolInterface;
use Tinywan\Mcp\Runtime\ExecutionContext;
use Tinywan\Mcp\Tool\Content\TextContent;
use Tinywan\Mcp\Tool\ToolCall;
use Tinywan\Mcp\Tool\ToolDefinition;
use Tinywan\Mcp\Tool\ToolResult;

final class CalculatorTool implements ToolInterface
{
    public function definition(): ToolDefinition
    {
        return new ToolDefinition(
            'calculate',
            'Add two numbers.',
            [
                'type' => 'object',
                'properties' => [
                    'left' => ['type' => 'number'],
                    'right' => ['type' => 'number'],
                ],
                'required' => ['left', 'right'],
                'additionalProperties' => false,
            ],
            [
                'type' => 'object',
                'properties' => [
                    'value' => ['type' => 'number'],
                ],
                'required' => ['value'],
                'additionalProperties' => false,
            ],
            'Calculator',
        );
    }

    public function call(ToolCall $call, ExecutionContext $context): ToolResult
    {
        $left = (float) $call->arguments['left'];
        $right = (float) $call->arguments['right'];
        $value = $left + $right;

        return ToolResult::success(
            [new TextContent((string) $value)],
            ['value' => $value],
        );
    }
}
```

The SDK validates the arguments against the Tool input Schema before invoking `call()` and validates
the optional structured result against the output Schema afterward.

### 3. Register the Tool on a Server

Replace `app/mcp/CalculatorServer.php` with:

```php
<?php

declare(strict_types=1);

namespace app\mcp;

use Tinywan\Mcp\Registry\RegisteredTool;
use Tinywan\Mcp\Registry\ServerDefinition;
use Tinywan\Mcp\Registry\ServerIdentity;
use Tinywan\Mcp\Security\AllowAllAuthorizer;
use Tinywan\Mcp\Security\AllowAnonymousAuthenticator;

final class CalculatorServer
{
    public static function definition(): ServerDefinition
    {
        $tool = new CalculatorTool();

        return new ServerDefinition(
            'calculator',
            '/mcp/calculator',
            new ServerIdentity('Calculator Server', '0.1.0'),
            [
                new RegisteredTool(
                    $tool->definition(),
                    CalculatorTool::class,
                ),
            ],
            new AllowAnonymousAuthenticator(),
            new AllowAllAuthorizer(),
        );
    }
}
```

This example explicitly permits anonymous access so that it can be tested locally. A production
Server should provide application-specific implementations of `AuthenticatorInterface` and
`AuthorizerInterface`. The defaults deny both authentication and authorization.

### 4. Add the Server to the Configuration

Update `config/plugin/tinywan/webman-mcp/servers.php`:

```php
<?php

declare(strict_types=1);

use app\mcp\CalculatorServer;

return [
    'servers' => [
        CalculatorServer::definition(),
    ],
];
```

Validate and inspect the configuration:

```bash
php webman mcp:inspect
php webman mcp:list
```

The list should include:

```text
SERVER calculator /mcp/calculator
  TOOL calculate
```

### 5. Start Webman

Use the start command appropriate for the Webman application. For example:

```bash
php start.php start
```

Webman projects running directly on Windows commonly use:

```bash
php windows.php
```

### 6. Call the Tool

Every MCP endpoint is POST-only. A request must advertise both response media types even though v0.1
never emits SSE. The method and Tool name Headers must mirror the JSON-RPC message:

```bash
curl -i http://127.0.0.1:8787/mcp/calculator \
  -X POST \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -H 'MCP-Protocol-Version: 2026-07-28' \
  -H 'Mcp-Method: tools/call' \
  -H 'Mcp-Name: calculate' \
  --data '{
    "jsonrpc":"2.0",
    "id":1,
    "method":"tools/call",
    "params":{
      "_meta":{
        "io.modelcontextprotocol/protocolVersion":"2026-07-28",
        "io.modelcontextprotocol/clientCapabilities":{}
      },
      "name":"calculate",
      "arguments":{"left":6,"right":7}
    }
  }'
```

The structured result contains a `value` of `13`. Omit `id` to send a notification; a processed
notification returns HTTP 202 with an empty body.

The SDK does not create, consume, or echo `Mcp-Session-Id` or `Last-Event-ID`.

## Commands

```text
mcp:install
make:mcp-server <name>
make:mcp-tool <name>
mcp:list
mcp:inspect
```

See [protocol compatibility](docs/PROTOCOL_COMPATIBILITY.md), [security](docs/SECURITY.md), and
[architecture](docs/ARCHITECTURE.md) before deploying. A more complete Calculator implementation is
available under [`examples/Calculator`](examples/Calculator).
