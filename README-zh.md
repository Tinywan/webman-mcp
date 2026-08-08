# Webman MCP

[English](README.md) | [简体中文](README-zh.md)

适用于 Webman 和 PHP 8.2+ 的无状态 MCP Server SDK。

- MCP 协议：`2026-07-28`
- 支持方法：`server/discover`、`tools/list`、`tools/call`
- 传输方式：无状态 HTTP POST
- 认证与授权：默认拒绝访问

协议详情请参阅官方 [MCP `2026-07-28` 发布说明](https://blog.modelcontextprotocol.io/posts/2026-07-28/)。

## 安装

在 Webman 2.1+ 项目中执行：

```bash
composer require tinywan/webman-mcp
```

安装时会自动将配置发布到 `config/plugin/tinywan/webman-mcp`。

## 快速开始

下面创建一个 Calculator MCP Server，并提供计算两数之和的 `calculate` 工具。

### 1. 生成文件

```bash
php webman make:mcp-server Calculator
php webman make:mcp-tool Calculator
```

生成以下文件：

```text
app/mcp/CalculatorServer.php
app/mcp/CalculatorTool.php
```

### 2. 实现 Tool

将 `app/mcp/CalculatorTool.php` 修改为：

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
            '计算两个数字之和。',
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

SDK 会根据 JSON Schema 校验输入参数和结构化输出。

### 3. 注册 Tool

将 `app/mcp/CalculatorServer.php` 修改为：

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

然后修改 `config/plugin/tinywan/webman-mcp/servers.php`：

```php
<?php

declare(strict_types=1);

use app\mcp\CalculatorServer;

return [
    'servers' => [CalculatorServer::definition()],
];
```

上面的本地示例显式允许匿名访问。生产环境应实现自己的 `AuthenticatorInterface` 和
`AuthorizerInterface`，不要直接允许匿名访问。

### 4. 检查并启动

```bash
php webman mcp:inspect
php webman mcp:list
php start.php start
```

调用 Tool，端口请根据实际环境调整：

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

响应中的 `structuredContent.value` 应为 `13`。

## 命令

请在 Webman 项目根目录执行：

| 命令 | 说明 |
| --- | --- |
| `php webman make:mcp-server <name>` | 生成 Server 文件 |
| `php webman make:mcp-tool <name>` | 生成 Tool 文件 |
| `php webman mcp:list` | 列出已配置的 Server 和 Tool |
| `php webman mcp:inspect` | 检查配置和 Schema |
| `php webman mcp:install` | 发布缺失的配置文件 |

`mcp:install` 不会覆盖已有文件。如果命令注册配置本身缺失，该命令将不可用。

## 文档

- [协议兼容性](docs/PROTOCOL_COMPATIBILITY.md)
- [安全说明](docs/SECURITY.md)
- [架构说明](docs/ARCHITECTURE.md)
- [Neuron AI 应用调用案例](docs/NEURON_AI_CLIENT.md)
- [固定版本的官方 Schema](resources/schema/README.md)
- [Calculator 示例](examples/Calculator)

## 应用如何调用

应用已经知道工具名和参数时，可以按照“快速开始”中的无状态 HTTP 协议直接调用。MCP URL、
认证信息、协议元数据和路由请求头应由应用服务端管理；浏览器或移动端应调用独立的业务接口，
不要允许终端用户传入任意 MCP URL。

Neuron AI 3.16 中，确定性业务使用 `McpClient::callTool()`：

```php
use app\neuron\WebmanMcpTransport;
use NeuronAI\MCP\McpClient;

$client = new McpClient([
    'transport' => new WebmanMcpTransport(
        'http://127.0.0.1:8787/mcp/calculator',
    ),
]);

$response = $client->callTool('calculate', [
    'left' => 6,
    'right' => 7,
]);

$value = $response['result']['structuredContent']['value'];
```

`WebmanMcpTransport` 是放在业务应用中的兼容层，负责把 Neuron 初始化流程转换为本 SDK 的
无状态 `server/discover` 调用，并补充 `2026-07-28` 所需的元数据和路由请求头，不应将其加入
本服务端 SDK。完整适配器、应用 Service、业务 HTTP 接口、Docker 网络和
`McpConnector + Agent` 示例见 [Neuron AI 应用调用案例](docs/NEURON_AI_CLIENT.md)。

## 在 Codex 和其他 Agent 中使用

先启动 Webman，并确保 Agent 可以访问 MCP Server 地址：

```bash
php start.php start
```

### Codex CLI

注册 HTTP Endpoint：

```bash
codex mcp add calculator --url http://127.0.0.1:8787/mcp/calculator
codex mcp list
```

如果 Server 使用 Bearer Token，请将 Token 保存到环境变量中：

```bash
codex mcp add calculator \
  --url http://127.0.0.1:8787/mcp/calculator \
  --bearer-token-env-var MCP_CALCULATOR_TOKEN
```

修改 MCP 配置后启动新的 Codex 会话，然后输入：

```text
使用 calculate 工具计算 6 加 7。
```

Codex 将发现该 MCP Server、调用 `calculate`，并返回 `13`。

### 其他 Agent

在 Agent 的 MCP 配置中添加远程 HTTP Server：

| 配置项 | 值 |
| --- | --- |
| 名称 | `calculator` |
| URL | `http://127.0.0.1:8787/mcp/calculator` |
| 传输方式 | HTTP / Streamable HTTP |
| 协议版本 | `2026-07-28` |

不同 Agent 的配置字段可能不同。客户端必须支持 MCP `2026-07-28`，并自动发送每次请求所需的
`_meta`、`MCP-Protocol-Version`、`Mcp-Method` 以及按条件发送的 `Mcp-Name` 请求头。

本 SDK 不支持 `initialize`、Session、协议降级或旧版 SSE Transport。

如果 Agent 运行在 Docker、虚拟机或其他主机中，`127.0.0.1` 指向 Agent 自身环境。请改用
Agent 能访问的地址，例如 `host.docker.internal`、容器服务名、局域网地址或域名。
