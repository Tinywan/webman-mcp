# Neuron AI 应用调用 MCP Server 案例

本文说明业务应用如何使用 Neuron AI 调用 `tinywan/webman-mcp` 提供的 MCP Server。
所有客户端代码都应放在业务应用中，不属于 `webman-mcp` 服务端 SDK。

## 1. 适用环境

| 组件 | 示例版本 |
| --- | --- |
| PHP | 8.2+，示例验证环境为 PHP 8.4 |
| Webman | 2.1+ |
| Neuron AI | `neuron-core/neuron-ai 3.16.1` |
| Webman MCP | `tinywan/webman-mcp 0.1.4` |
| MCP 协议 | `2026-07-28` |

在业务应用中安装：

```bash
composer require neuron-core/neuron-ai:^3.16
```

本案例假设 MCP Server 暴露以下工具：

```text
Endpoint: POST /mcp/order
Tool:     query_order_snapshot
Input:    {"order_no":"ORDER-20260621-1001"}
```

## 2. 两种调用方式

```text
固定业务调用
  → NeuronAI\MCP\McpClient
  → 指定 tools/call
  → MCP Server

自然语言调用
  → Neuron Agent
  → McpConnector 提供工具定义
  → 模型选择工具和参数
  → MCP Server
```

| 场景 | 推荐方式 | 是否需要模型 API |
| --- | --- | --- |
| 已知工具名和参数 | `McpClient::callTool()` | 否 |
| 定时任务、内部接口、确定性业务 | `McpClient::callTool()` | 否 |
| 用户输入自然语言 | `McpConnector + Agent` | 是 |
| 多工具选择和结果总结 | `McpConnector + Agent` | 是 |

固定工具调用不需要经过大模型。这样延迟更低、没有模型费用，结果也更稳定。

## 3. 协议兼容性

`webman-mcp 0.1.x` 是无状态 MCP Server，仅支持：

- `server/discover`
- `tools/list`
- `tools/call`

每个请求必须包含 `params._meta`、`MCP-Protocol-Version`、`Mcp-Method`，调用工具时还必须
发送 `Mcp-Name`。

Neuron AI 3.16.1 自带 HTTP transport 会发送 `initialize` 和
`notifications/initialized`，默认协议版本也是 `2024-11-05`。因此不能直接写：

```php
// 不适用于 webman-mcp 0.1.x。
McpConnector::make(['url' => 'http://127.0.0.1:8788/mcp/order']);
```

业务应用需要提供一个 transport 适配器：

- 把 `initialize` 转换为 `server/discover`；
- 忽略无状态服务不需要的 `notifications/initialized`；
- 为每次请求注入 `2026-07-28` 元数据和镜像请求头。

这只是客户端兼容层，不会改变 MCP Server 的协议行为。

## 4. 在业务应用中添加 Transport

创建 `app/neuron/WebmanMcpTransport.php`：

```php
<?php

declare(strict_types=1);

namespace app\neuron;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use NeuronAI\MCP\McpException;
use NeuronAI\MCP\McpTransportInterface;
use stdClass;

final class WebmanMcpTransport implements McpTransportInterface
{
    private const PROTOCOL_VERSION = '2026-07-28';

    private ClientInterface $http;
    private ?array $response = null;
    private bool $connected = false;

    public function __construct(
        private readonly string $url,
        private readonly array $headers = [],
        ?ClientInterface $http = null,
    ) {
        $this->http = $http ?? new Client(['timeout' => 30]);
    }

    public function connect(): void
    {
        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw new McpException("Invalid MCP URL: {$this->url}");
        }

        $this->connected = true;
    }

    public function send(array $data): void
    {
        if (!$this->connected) {
            throw new McpException('MCP transport is not connected.');
        }

        $method = $data['method'] ?? null;
        if (!is_string($method) || $method === '') {
            throw new McpException('MCP request method is required.');
        }

        if ($method === 'notifications/initialized') {
            return;
        }

        $method = $method === 'initialize' ? 'server/discover' : $method;
        $params = $method === 'server/discover' ? [] : ($data['params'] ?? []);
        $params['_meta'] = [
            'io.modelcontextprotocol/protocolVersion' => self::PROTOCOL_VERSION,
            'io.modelcontextprotocol/clientCapabilities' => new stdClass(),
            'io.modelcontextprotocol/clientInfo' => [
                'name' => 'neuron-ai-webman-client',
                'version' => '1.0.0',
            ],
        ];

        $payload = [
            'jsonrpc' => '2.0',
            'id' => $data['id'] ?? null,
            'method' => $method,
            'params' => $params,
        ];

        $headers = array_merge($this->headers, [
            'Accept' => 'application/json, text/event-stream',
            'Content-Type' => 'application/json',
            'MCP-Protocol-Version' => self::PROTOCOL_VERSION,
            'Mcp-Method' => $method,
        ]);

        if ($method === 'tools/call') {
            $headers['Mcp-Name'] = (string) ($params['name'] ?? '');
        }

        $httpResponse = $this->http->request('POST', $this->url, [
            'headers' => $headers,
            'json' => $payload,
            'http_errors' => false,
        ]);

        $decoded = json_decode(
            (string) $httpResponse->getBody(),
            true,
            64,
            JSON_THROW_ON_ERROR,
        );

        if (!is_array($decoded)) {
            throw new McpException('MCP response must be a JSON object.');
        }

        $this->response = $decoded;
    }

    public function receive(): array
    {
        if ($this->response === null) {
            throw new McpException('No MCP response is available.');
        }

        $response = $this->response;
        $this->response = null;

        return $response;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->response = null;
    }
}
```

如需 Bearer Token，可通过第二个构造参数传入：

```php
new WebmanMcpTransport($url, [
    'Authorization' => 'Bearer ' . getenv('ORDER_MCP_TOKEN'),
]);
```

## 5. 使用 McpClient 直接调用

建议在业务应用中再封装一层 Service：

```php
<?php

declare(strict_types=1);

namespace app\service;

use app\neuron\WebmanMcpTransport;
use NeuronAI\MCP\McpClient;
use NeuronAI\MCP\McpException;

final class OrderMcpClient
{
    public function query(string $orderNo): array
    {
        $client = new McpClient([
            'transport' => new WebmanMcpTransport(
                (string) config('neuron.order_mcp_url'),
            ),
        ]);

        $response = $client->callTool('query_order_snapshot', [
            'order_no' => $orderNo,
        ]);

        if (isset($response['error'])) {
            throw new McpException(
                (string) ($response['error']['message'] ?? 'Unknown MCP error'),
            );
        }

        return $response['result']['structuredContent'] ?? [];
    }
}
```

配置 `config/neuron.php`：

```php
<?php

declare(strict_types=1);

return [
    'order_mcp_url' => getenv('ORDER_MCP_URL')
        ?: 'http://127.0.0.1:8788/mcp/order',
];
```

业务中调用：

```php
$order = (new \app\service\OrderMcpClient())
    ->query('ORDER-20260621-1001');
```

优先读取 `structuredContent`，无需再次解析 `content[0].text`。

## 6. 对外提供业务 HTTP 接口

MCP 协议头属于内部通信细节。推荐让调用方只提交业务参数：

```php
<?php

declare(strict_types=1);

namespace app\controller;

use app\service\OrderMcpClient;
use support\Request;
use support\Response;
use Throwable;

final class OrderMcpController
{
    public function query(Request $request): Response
    {
        $orderNo = trim((string) $request->post('order_no', ''));
        if ($orderNo === '') {
            return json([
                'code' => 42200,
                'message' => 'order_no 不能为空',
                'data' => null,
            ])->withStatus(422);
        }

        try {
            return json([
                'code' => 0,
                'message' => 'ok',
                'data' => (new OrderMcpClient())->query($orderNo),
            ]);
        } catch (Throwable $exception) {
            return json([
                'code' => 50200,
                'message' => 'MCP 调用失败',
                'data' => null,
            ])->withStatus(502);
        }
    }
}
```

注册路由：

```php
use Webman\Route;

Route::post('/api/neuron/order/query', [
    app\controller\OrderMcpController::class,
    'query',
]);
```

应用调用方使用普通 JSON HTTP 请求：

```bash
curl -X POST http://127.0.0.1:8118/api/neuron/order/query \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json' \
  -d '{"order_no":"ORDER-20260621-1001"}'
```

响应示例：

```json
{
  "code": 0,
  "message": "ok",
  "data": {
    "order_no": "ORDER-20260621-1001",
    "status": "SHIPPED",
    "ship_company": "顺丰速运",
    "tracking_no": "SF1234567890"
  }
}
```

## 7. 使用 McpConnector 让 Agent 自主调用

自然语言场景可以把 MCP 工具注册给 Agent：

```php
<?php

declare(strict_types=1);

namespace app\neuron;

use NeuronAI\Agent\Agent;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\OpenAI\OpenAI;

final class OrderAgent extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new OpenAI(
            key: (string) getenv('OPENAI_API_KEY'),
            model: getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini',
        );
    }

    protected function instructions(): string
    {
        return '你是订单客服助手。查询订单时必须调用 MCP 工具，禁止编造订单状态。';
    }

    protected function tools(): array
    {
        return [
            ...McpConnector::make([
                'transport' => new WebmanMcpTransport(
                    (string) config('neuron.order_mcp_url'),
                ),
            ])->only(['query_order_snapshot'])->tools(),
        ];
    }
}
```

调用 Agent：

```php
use app\neuron\OrderAgent;
use NeuronAI\Chat\Messages\UserMessage;

$message = OrderAgent::make()
    ->chat(new UserMessage(
        '帮我查询订单 ORDER-20260621-1001 的物流进度',
    ))
    ->getMessage();

echo $message->getContent();
```

Agent 模式通常包含模型工具选择、MCP 调用和模型结果总结，可能发生多次模型请求。需要配置模型
API Key，并评估延迟和费用。

## 8. Docker 地址

| PHP 应用运行位置 | MCP URL 示例 |
| --- | --- |
| 与 MCP Server 相同容器 | `http://127.0.0.1:8788/mcp/order` |
| Docker 容器访问 Windows 宿主机 | `http://host.docker.internal:8118/mcp/order` |
| Docker Compose 不同容器 | `http://<service-name>:8788/mcp/order` |
| 宿主机进程 | `http://127.0.0.1:8118/mcp/order` |

容器中的 `127.0.0.1` 始终指向当前容器，不指向宿主机。

## 9. 常见错误

### `Method not found: initialize`

Neuron 默认 transport 与 `webman-mcp 0.1.x` 协议边界不兼容。使用上面的应用侧
`WebmanMcpTransport`。

### `Unsupported protocol version`

确认 `_meta` 和 `MCP-Protocol-Version` 都是 `2026-07-28`。

### `mcp-method does not match Body`

`Mcp-Method` 必须与 JSON-RPC Body 的 `method` 完全一致。工具调用的 `Mcp-Name` 也必须与
`params.name` 一致。

### `Failed to connect to 127.0.0.1`

检查应用运行位置并使用上一节对应的 Docker 地址。

### HTTP `502`

业务接口能够访问，但它调用的 MCP Server 不可用或返回了协议错误。检查 MCP URL、服务状态和
MCP Server 日志。

## 10. 安全建议

- 生产 MCP Server 不应使用匿名认证和全放行授权。
- MCP URL 和 Token 应由服务端配置，不接受终端用户任意传入，避免 SSRF。
- 不要把 Bearer Token 写入日志、异常响应或源码。
- 对外业务接口应校验参数，并把 MCP 错误映射成稳定的业务错误码。
- Agent 只注册实际需要的工具，可通过 `only([...])` 限制工具范围。

