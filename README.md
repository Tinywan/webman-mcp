# Webman MCP

`tinywan/webman-mcp` is a stateless MCP Server SDK for Webman and PHP 8.2+. Version 0.1 implements
only protocol `2026-07-28` and the `server/discover`, `tools/list`, and `tools/call` methods.

## Install

```bash
composer require tinywan/webman-mcp
```

Webman publishes the package configuration from `src/config/plugin/tinywan/webman-mcp` to
`config/plugin/tinywan/webman-mcp` during installation. Run `php webman mcp:install` only to publish
missing assets manually; it preserves every existing application file and reports conflicts.

Add a Server definition to
`config/plugin/tinywan/webman-mcp/servers.php`. For a local Calculator demonstration:

```php
<?php

declare(strict_types=1);

use Tinywan\Mcp\Examples\Calculator\CalculatorServer;

return [
    'servers' => [CalculatorServer::definition()],
];
```

The Calculator explicitly enables anonymous access for local demonstration. Production Servers should
configure an application authenticator; the `ServerDefinition` default denies authentication and
authorization.

## Request

Every MCP endpoint is POST-only. A modern request must advertise both response media types even though
v0.1 never emits SSE:

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
      "arguments":{"operation":"multiply","left":6,"right":7}
    }
  }'
```

Omit `id` to send a notification. A processed notification returns HTTP 202 with an empty body. The
SDK does not create, consume, or echo `Mcp-Session-Id` or `Last-Event-ID`.

## Commands

```text
mcp:install
make:mcp-server <name>
make:mcp-tool <name>
mcp:list
mcp:inspect
```

See [protocol compatibility](docs/PROTOCOL_COMPATIBILITY.md), [security](docs/SECURITY.md), and
[architecture](docs/ARCHITECTURE.md) before deploying.
