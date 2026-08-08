# Webman MCP

Stateless MCP Server SDK for Webman and PHP 8.2+.

- MCP protocol: `2026-07-28`
- Methods: `server/discover`, Tools, Resources, Prompts, Completion, and Subscriptions
- Transport: stateless HTTP POST with bounded event-stream responses for `subscriptions/listen`
- Authentication and authorization: denied by default

See the official [MCP `2026-07-28` release](https://blog.modelcontextprotocol.io/posts/2026-07-28/).
Production deployments should also follow [the deployment guide](docs/DEPLOYMENT.md) for digest-only
Bearer credentials, governance controls, and redacted observability.

## Installation

Run in a Webman 2.1+ project:

```bash
composer require tinywan/webman-mcp
```

The package automatically publishes its configuration to
`config/plugin/tinywan/webman-mcp`.

## Quick Start

The following example exposes a `calculate` Tool that adds two numbers.

### 1. Generate the files

```bash
php webman make:mcp-server Calculator
php webman make:mcp-tool Calculator
```

This creates `app/mcp/CalculatorServer.php` and `app/mcp/CalculatorTool.php`.

### 2. Implement the Tool

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
                'properties' => ['value' => ['type' => 'number']],
                'required' => ['value'],
                'additionalProperties' => false,
            ],
        );
    }

    public function call(ToolCall $call, ExecutionContext $context): ToolResult
    {
        $value = (float) $call->arguments['left'] + (float) $call->arguments['right'];

        return ToolResult::success(
            [new TextContent((string) $value)],
            ['value' => $value],
        );
    }
}
```

Save it as `app/mcp/CalculatorTool.php`. Arguments and structured output are validated against their
JSON Schemas.

### 3. Register the Tool

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
            new ServerIdentity('Calculator', '1.0.0'),
            [new RegisteredTool($tool->definition(), CalculatorTool::class)],
            new AllowAnonymousAuthenticator(),
            new AllowAllAuthorizer(),
        );
    }
}
```

Save it as `app/mcp/CalculatorServer.php`, then update
`config/plugin/tinywan/webman-mcp/servers.php`:

```php
<?php

declare(strict_types=1);

use app\mcp\CalculatorServer;

return [
    'servers' => [CalculatorServer::definition()],
];
```

Anonymous access is enabled only for this local example. Production Servers should provide explicit
implementations of `AuthenticatorInterface` and `AuthorizerInterface`.

### 4. Verify and run

```bash
php webman mcp:inspect
php webman mcp:list
php start.php start
```

Call the Tool (replace the port if needed):

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

The response contains `structuredContent.value: 13`.

## Commands

Run commands from the Webman project root:

| Command | Description |
| --- | --- |
| `php webman make:mcp-server <name>` | Generate a Server scaffold |
| `php webman make:mcp-tool <name>` | Generate a Tool scaffold |
| `php webman mcp:list` | List configured Servers and Tools |
| `php webman mcp:inspect` | Validate configuration and Schemas |
| `php webman mcp:install` | Publish individual missing configuration files |

`mcp:install` never overwrites existing files. It is not available when the command registration
configuration itself is missing.

## Documentation

- [Protocol compatibility](docs/PROTOCOL_COMPATIBILITY.md)
- [Security](docs/SECURITY.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Contributing and release checks](docs/CONTRIBUTING.md)
- [Pinned official schema](resources/schema/README.md)
- [Calculator example](examples/Calculator)
- [Library Resource example](examples/Library)
- [Assistant Prompt example](examples/Assistant)

## Using with Codex and Other Agents

Start the Webman Server first and make sure the Agent can access its URL:

```bash
php start.php start
```

### Codex CLI

Register the HTTP endpoint:

```bash
codex mcp add calculator --url http://127.0.0.1:8787/mcp/calculator
codex mcp list
```

If the Server uses Bearer authentication, store the token in an environment variable and register
its name instead of putting the token in the command:

```bash
codex mcp add calculator \
  --url http://127.0.0.1:8787/mcp/calculator \
  --bearer-token-env-var MCP_CALCULATOR_TOKEN
```

Start a new Codex session after changing the MCP configuration, then ask:

```text
Use the calculate Tool to add 6 and 7.
```

Codex should discover the Server and call `calculate`, returning `13`.

### Other Agents

In the Agent's MCP settings, add a remote HTTP Server with these values:

| Setting | Value |
| --- | --- |
| Name | `calculator` |
| URL | `http://127.0.0.1:8787/mcp/calculator` |
| Transport | HTTP / Streamable HTTP |
| Protocol version | `2026-07-28` |

Configuration field names differ between Agents. A compatible Agent must support MCP `2026-07-28`
and send the official per-request `_meta`, `MCP-Protocol-Version`, `Mcp-Method`, and conditional
`Mcp-Name` routing Headers. This SDK does not support `initialize`, sessions, protocol downgrade,
legacy GET/DELETE SSE transport, or event replay. `subscriptions/listen` uses an authorized,
bounded POST response and delivers events at most once.

If the Agent runs in Docker, a VM, or another host, `127.0.0.1` points to that environment rather than
the Webman host. Use an address reachable from the Agent, such as `host.docker.internal`, a container
service name, or the Server's LAN/domain address.
