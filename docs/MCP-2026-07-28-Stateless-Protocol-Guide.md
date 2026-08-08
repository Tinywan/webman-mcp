# MCP 2026-07-28 无状态协议技术解读与落地指南

> 本文参考 [giantswarm/muster 的《Stateless protocol (MCP 2026-07-28)》](https://github.com/giantswarm/muster/blob/074a26138f49a93153056ca98d55ab44b8cdb562/docs/explanation/mcp-2026-07-28/01-stateless-protocol.md) 的结构与要点，结合 **webman-mcp**（无状态 MCP Server SDK）与 **Neuron-AI**（PHP Agentic 框架）给出可运行的落地示例。
>
> - 官方发布公告：[MCP 2026-07-28 release candidate](https://blog.modelcontextprotocol.io/posts/2026-07-28-release-candidate/)
> - 官方 SEP 列表：SEP-2575 / 2567 / 2260 / 2322 / 2243 / 2549 / 414
> - 协议版本：`2026-07-28`（release candidate，未最终冻结）

---

## 1. 概述：协议层无状态化

MCP `2026-07-28` 发布候选最核心的改动，是让协议在**协议层彻底无状态**。

六份规范增强提案（SEP）+ W3C Trace Context 约定（SEP-414）一起，移除了：

- `initialize` 三次握手
- `Mcp-Session-Id` 头与会话概念
- 长连接 GET SSE 流
- 自由漂浮的 server→client 请求通道

并用以下机制取而代之：

| 取代对象 | 新机制 | 出处 |
|---------|--------|------|
| `initialize` 握手协商的版本/身份/能力 | 每个请求在 `_meta` 自描述 | SEP-2575 |
| 会话粘性路由 | 无状态 + 显式 handle（`create_basket()` → `basket_id`） | SEP-2567 |
| 常驻 SSE 的服务端下发 | 内联 `InputRequiredResult`（MRTR）多轮 | SEP-2260 / 2322 |
| 深包解析才能路由 | `Mcp-Method` / `Mcp-Name` 头镜像 | SEP-2243 |
| 列表结果缓存失效 | `ttlMs` / `cacheScope` | SEP-2549 |
| 分布式追踪 | `_meta` 承载 `traceparent` / `tracestate` / `baggage` | SEP-414 |

**一句话：每个请求都是自描述的、可独立处理的纯函数（request → response），可以在普通 HTTP 负载均衡器后面水平扩展，无需粘性会话。**

---

## 2. 协议层怎么变（对照旧版）

### 2.1 握手与会话消失 —— SEP-2575 / SEP-2567

**之前（2025-11-25）**：客户端先 POST `initialize`，服务端返回能力 + `Mcp-Session-Id`，此后每个请求都要带该头。路由被钉在签发了该 ID 的那台实例上，水平扩展必须共享会话存储。

**之后（2026-07-28）**：协议版本、客户端软件身份、客户端能力**在每个请求的 `_meta` 中携带**：

```jsonc
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "query_order_snapshot",
    "arguments": { "order_no": "ORDER-20260621-1001" },
    "_meta": {
      "io.modelcontextprotocol/protocolVersion": "2026-07-28",
      "io.modelcontextprotocol/clientInfo": {
        "name": "neuron-agent",
        "version": "1.0.0"
      },
      "io.modelcontextprotocol/clientCapabilities": {}
    }
  }
}
```

要点：
- `protocolVersion` / `clientInfo` / `clientCapabilities` 三个字段**必填**，逐请求声明，**不从先前流量推断**。
- 协议版本同时镜像在 HTTP 头 `MCP-Protocol-Version` 上，头与 body 必须一致。
- 新增 `server/discover` RPC：客户端可先查询服务端支持的版本、能力、身份、说明，但调用它**是可选的**——任何 RPC 都可以直接调用，版本不匹配时返回 `UnsupportedProtocolVersionError`（带 `supported` 列表），客户端可据此重试到兼容版本。
- 缺失必需能力返回 `MissingRequiredClientCapabilityError`（`-32003`，HTTP 400）。

SEP-2567 移除 `Mcp-Session-Id` 和协议级会话。后果：`tools/list` / `resources/list` / `prompts/list` 的结果**不得随连接变化**，也不能受同一连接上其他请求的副作用影响。

**重要澄清**：SEP-2567 移除的是**协议层的抽象**，不是应用状态。需要跨调用状态的服务器，用 HTTP API 一直以来的做法——一个 `create_basket()` 工具返回 `basket_id`，后续工具把它当普通字符串参数接受。**Handle 是工具设计模式，不是协议原语。**

### 2.2 服务端 → 客户端请求重构 —— SEP-2260 / SEP-2322（MRTR）

无状态协议仍需要服务端在调用中途向客户端要东西（elicitation 提示、sampling 采样、`roots/list` 查询）。两个 SEP 重建了这条链路，让它无需持久连接也能工作。

**SEP-2260** 把 `roots/list`、`sampling/createMessage`、`elicitation/create` 变成规范要求：只能**在处理客户端发起的请求期间**（如 `tools/call`）发出，禁止自由漂浮的服务端请求。

**SEP-2322（MRTR, Multi Round-Trip Requests）** 改变了提示的投递方式。不再挂一条 SSE 流等服务端回复，而是内联返回一个 `InputRequiredResult`：

```json
{
  "resultType": "inputRequired",
  "inputRequests": {
    "confirm": {
      "type": "elicitation",
      "message": "确认删除 3 个文件吗？",
      "schema": { "type": "boolean" }
    }
  },
  "requestState": "eyJzdGVwIjoxLCJmaWxlcyI6WyJhIiwiYiIsImMiXX0="
}
```

- `inputRequests`：服务端键控的请求映射（复用 `CreateMessageRequest` / `ElicitRequest` / `ListRootsRequest` 形状）。
- `requestState`：不透明的服务端编码状态，客户端原样回传即可。
- 客户端收集答案后，**重新发起原始请求**，附带原参数 + `inputResponses`（与 `inputRequests` 同键控）+ 回传的 `requestState`。
- 因为恢复所需的一切都在 payload 里，**任何服务端副本都能接手重试**，无需粘性路由或共享存储。

### 2.3 可路由、可缓存的流量 —— SEP-2243 / SEP-2549

**SEP-2243（HTTP 标准化）**：每个 Streamable HTTP POST 必须携带 `Mcp-Method`（镜像 JSON-RPC `method`），`tools/call` / `resources/read` / `prompts/get` 还必须带 `Mcp-Name`（镜像 `params.name` 或 `params.uri`）。**服务端（及任何处理 body 的中间件）必须拒绝头与 body 不一致的请求**，返回 `HeaderMismatch` 错误和 HTTP 400。

```http
POST /mcp/order HTTP/1.1
Content-Type: application/json
Accept: application/json, text/event-stream
MCP-Protocol-Version: 2026-07-28
Mcp-Method: tools/call
Mcp-Name: query_order_snapshot
```

SEP-2243 还引入 `x-mcp-header` JSON-Schema 扩展：工具可把某些基本类型参数镜像成 `Mcp-Param-{Name}` 头，便于中间层做 region/tenant 路由。客户端必须支持该扩展，非 ASCII 值用 `=?base64?…?=` 编码。

**SEP-2549（列表结果 TTL）**：`tools/list`、`resources/list`、`prompts/list`、`resources/read`、`resources/templates/list` 的结果新增 `CacheableResult` 接口，含两个必填字段：

- `ttlMs`：整数毫秒，`>= 0`，`0` 表示立即可过期
- `cacheScope`：`"public"` 或 `"private"`，`"private"` 禁止共享缓存给其他用户

语义模拟 HTTP `Cache-Control`。TTL **补充而非替代** `notifications/*/list_changed`。配合 SEP-2567 移除逐连接变化，列表结果获得了**部署级稳定缓存键**（部署 + 认证主体），不必为每个新连接重新拉取。

### 2.4 跨 SDK 与网关可追踪 —— SEP-414

`_meta` 是 W3C Trace Context 的载体，`traceparent` / `tracestate` / `baggage` 这三个键**豁免** `_meta` 键的强制 reverse-DNS 前缀（其余键必须带 `io.modelcontextprotocol/` 前缀）。

```jsonc
"_meta": {
  "io.modelcontextprotocol/protocolVersion": "2026-07-28",
  "traceparent": "00-0af7651916cd43dd8448eb211c80319c-00f067aa0ba902b7-01",
  "tracestate": "rojo=00f067aa0ba902b7"
}
```

这样，宿主机应用里开始的 trace 可以穿过客户端 SDK → MCP 服务端 → 服务端调用的下游，在任意 OpenTelemetry 后端里形成一棵完整 span 树。

---

## 3. 落地一：用 webman-mcp 构建无状态 MCP Server

**webman-mcp** 是 Webman 2.1+ / PHP 8.2+ 的无状态 MCP Server SDK，协议锁定 `2026-07-28`，支持 `server/discover` / `tools/list` / `tools/call` 三个方法。

### 3.1 安装与脚手架

```bash
composer require tinywan/webman-mcp
php webman make:mcp-server Calculator
php webman make:mcp-tool Calculator
```

生成 `app/mcp/CalculatorServer.php` 和 `app/mcp/CalculatorTool.php`。

### 3.2 定义一个 Tool

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
                    'left'  => ['type' => 'number'],
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
            content: [new TextContent((string) $value)],
            structuredContent: ['value' => $value],
        );
    }
}
```

`ToolDefinition` 同时声明 `inputSchema` 与 `outputSchema`，webman-mcp 会在调用前后用 JSON Schema 2020-12 分别校验入参与结构化输出——声明即契约。

### 3.3 注册 Server

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
            new AllowAnonymousAuthenticator(),  // 演示用，生产请替换
            new AllowAllAuthorizer(),           // 演示用，生产请替换
        );
    }
}
```

在 `config/plugin/tinywan/webman-mcp/servers.php` 注册：

```php
<?php

declare(strict_types=1);

use app\mcp\CalculatorServer;

return [
    'servers' => [CalculatorServer::definition()],
];
```

### 3.4 运行与验证

```bash
php webman mcp:list       # 查看注册的 server 拓扑
php webman mcp:inspect    # 校验配置并给出诊断
php start.php start       # 启动 webman
```

无状态客户端的完整调用（注意 `_meta` + 头镜像都是**必填**）：

```bash
curl -X POST http://127.0.0.1:8118/mcp/calculator \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -H "mcp-protocol-version: 2026-07-28" \
  -H "mcp-method: tools/call" \
  -H "mcp-name: calculate" \
  -d '{
    "jsonrpc": "2.0",
    "id": 1,
    "method": "tools/call",
    "params": {
      "name": "calculate",
      "arguments": {"left": 2, "right": 3},
      "_meta": {
        "io.modelcontextprotocol/protocolVersion": "2026-07-28",
        "io.modelcontextprotocol/clientCapabilities": {},
        "io.modelcontextprotocol/clientInfo": {"name": "curl", "version": "1.0.0"}
      }
    }
  }'
```

webman-mcp 强制校验：
1. `_meta` 必须是对象，且含带命名空间的协议版本 / 客户端能力 / 客户端信息
2. 头 `mcp-protocol-version` / `mcp-method` / `mcp-name` 与 body 必须镜像一致，否则 `HeaderMismatch`（-32020）
3. 缺任何一项即拒绝 —— 这正是 2026-07-28 无状态协议的自描述要求

### 3.5 生产化清单

| 项 | 演示默认 | 生产建议 |
|----|---------|---------|
| 认证 | `AllowAnonymousAuthenticator` | 实现 `AuthenticatorInterface`（Bearer / JWT） |
| 授权 | `AllowAllAuthorizer` | 按 principal 做工具级授权 |
| Origin | `OriginPolicy` 已启用 | 收紧到指定域名 |
| 限流/审计 | 无 | 网关层补充 |

---

## 4. 落地二：用 Neuron-AI 消费 MCP Server

**Neuron-AI**（`neuron-core/neuron-ai`）是 PHP 生态的 Agentic 框架（类 LangChain/LlamaIndex），内置 LLM 提供器、工具、RAG、多智能体编排。它的 **`McpConnector`** 能把任意 MCP Server 暴露的工具自动接入 Agent，无需手写工具逻辑。

```bash
composer require neuron-core/neuron-ai
```

### 4.1 本地 MCP Server（stdio）

```php
<?php

declare(strict_types=1);

namespace app\neuron;

use NeuronAI\MCP\McpConnector;

final class CalculatorAgent extends Agent
{
    protected function tools(): array
    {
        return [
            ...McpConnector::make([
                'command' => 'php',
                'args' => ['/var/www/chunfen/mcp_server.php'],
            ])->tools(),
        ];
    }
}
```

### 4.2 远程 MCP Server（Streamable HTTP）

```php
protected function tools(): array
{
    return [
        ...McpConnector::make([
            'url'     => 'http://erp.chutang66.com/mcp/order',
            'token'   => env('MCP_TOKEN'),
            'timeout' => 30,
            'headers' => [
                'X-Tenant' => 'chunfen',
            ],
        ])->tools(),
    ];
}
```

当 Agent 决定调用某个工具时，Neuron 生成对应请求发往 MCP Server 并取回结果，外部工具用起来和本地定义的工具完全一样。

---

## 5. 真实摩擦点：Neuron 客户端 × 2026-07-28 无状态服务端

**这是本文最有价值的一节。** Neuron 的 `McpConnector` 目前走的是**传统 Streamable HTTP**（`initialize` 握手 + `token` 认证 + 可选 SSE），而 webman-mcp 是 **2026-07-28 无状态**（无握手、强制每请求 `_meta`、强制头镜像）。两者直接对接会遇到：

| 摩擦点 | 现象 | 根因 |
|--------|------|------|
| 无 `_meta` | 服务端返回 `params._meta must be an object` | 2026-07-28 要求每请求自描述；老客户端不发 |
| 无头镜像 | 服务端返回 `Header mismatch`（-32020） | SEP-2243 要求 `Mcp-Method`/`Mcp-Name` 镜像，老客户端不设 |
| 尝试 `initialize` | 服务端返回 `Method not found`（-32601） | 2026-07-28 移除握手，`initialize` 不存在 |
| 协议版本不匹配 | `Unsupported protocol version`（-32022） | 版本写死在 `Version::PROTOCOL`，不做协商降级 |

**本质**：客户端与服务器停留在协议的两个世代上。这是任何自研/第三方客户端对接 2026-07-28 服务端时都会遇到的一类问题，不是 webman-mcp 的缺陷。

### 缓解路径（按推荐排序）

**A. 等/促客户端升级到 2026-07-28。** 官方 Go / Python SDK 已提供无状态路径（`StreamableHTTPOptions.Stateless = true`、`python-sdk v2.0.0a3`）。Neuron 若跟进，摩擦自动消失。这是最干净的方向。

**B. 在 webman-mcp 增加"宽松模式"开关（可选兼容）。** 增加一个配置项（如 `strict: false`），在 `ProtocolParser` 中对 `_meta` 与头镜像做可选校验、并补一个 `initialize` 的兼容桩返回。适合"必须马上接老客户端"的过渡期。代价：偏离 2026-07-28 规范。

**C. 前置一个协议翻译网关/代理。** 类似 [mcpsense-proxy](https://www.npmjs.com/package/mcpsense-proxy) 的桥接：对外维持老协议握手，对内翻译成 2026-07-28 无状态。适合需要同时服务两代客户端的网关型部署，复杂度最高。

**D. 服务端直接降级到老协议。** 如果你的客户端生态短期内无法跟进 2026-07-28，可让 webman-mcp 支持 2025-11-25 握手路径（dual-stack）。这是 Go/Python SDK 的默认策略，但需要 webman-mcp 实现版本协商。

> 建议：**短期**用 B 或 D 平滑过渡，**长期**跟随 A 迁到纯无状态。若你掌控全部客户端（自研 Agent），则无摩擦，直接用 2026-07-28 即可。

---

## 6. 总结与迁移清单

### 为什么值得无状态化

- **水平扩展零负担**：每请求自描述，普通 L4/L7 负载均衡 + round-robin 即可，无粘性会话
- **中间层可路由**：`Mcp-Method` / `Mcp-Name` 头让网关/监控不解包 JSON 就能路由与观测
- **请求可追踪**：`_meta` 承载 Trace Context，跨 SDK / 网关 / 下游贯通
- **缓存可共享**：`ttlMs` + `cacheScope` + 无会话变化 → 部署级稳定缓存键

### 服务端（webman-mcp）落地清单

- [ ] `composer require tinywan/webman-mcp`
- [ ] `make:mcp-server` / `make:mcp-tool` 生成脚手架
- [ ] 定义 `ToolDefinition`（声明 input/output schema，**声明即契约**）
- [ ] 注册 `ServerDefinition`，配置 `servers.php`
- [ ] `mcp:inspect` / `mcp:list` 校验，`start.php start` 启动
- [ ] 生产：替换 `AllowAnonymousAuthenticator` / `AllowAllAuthorizer`，收紧 Origin
- [ ] 核对错误码是否与目标客户端/规范一致（见下）

### 客户端（Neuron-AI）落地清单

- [ ] `composer require neuron-core/neuron-ai`
- [ ] 用 `McpConnector` 接入本地/远程 MCP Server
- [ ] **确认客户端与 2026-07-28 无状态协议兼容**，否则按 §5 处理
- [ ] 配置 `token` / `timeout` / 自定义 `headers`

### 迁移注意

- **错误码核对**：HeaderMismatch 错误码在 SEP-2243 演进中出现过 **-32001** 与 **-32020** 两种写法（[muster 文档](https://github.com/giantswarm/muster/blob/074a26138f49a93153056ca98d55ab44b8cdb562/docs/explanation/mcp-2026-07-28/01-stateless-protocol.md) 写 -32001，[Go SDK](https://github.com/modelcontextprotocol/go-sdk/releases/tag/v1.7.0-pre.1) 与 webman-mcp 用 -32020）。协议尚在 RC 阶段，**务必以你锁定的官方 schema 为准**核对。
- **应用状态 ≠ 协议状态**：跨调用状态用工具 handle 表达（`create_basket()` → `basket_id`），不要靠会话。
- **版本协商**：`Version::PROTOCOL` 写死 2026-07-28，不做降级。需要兼容老客户端时参考 §5。

---

## 参考资料

- [SEP-2575 — Make MCP Stateless](https://github.com/modelcontextprotocol/modelcontextprotocol/pull/2575)
- [SEP-2567 — Sessionless MCP via Explicit State Handles](https://github.com/modelcontextprotocol/modelcontextprotocol/pull/2567)
- [SEP-2260 — Require Server requests to be associated with a Client request](https://github.com/modelcontextprotocol/modelcontextprotocol/pull/2260)
- [SEP-2322 — Multi Round-Trip Requests (MRTR)](https://github.com/modelcontextprotocol/modelcontextprotocol/pull/2322)
- [SEP-2243 — HTTP Header Standardization for Streamable HTTP Transport](https://github.com/modelcontextprotocol/modelcontextprotocol/pull/2243)
- [SEP-2549 — TTL for List Results](https://github.com/modelcontextprotocol/modelcontextprotocol/pull/2549)
- [SEP-414 — Document OpenTelemetry Trace Context Propagation Conventions](https://github.com/modelcontextprotocol/modelcontextprotocol/pull/414)
- 官方公告：[2026-07-28 release candidate](https://blog.modelcontextprotocol.io/posts/2026-07-28-release-candidate/)
- 参考文档：[muster — Stateless protocol (MCP 2026-07-28)](https://github.com/giantswarm/muster/blob/074a26138f49a93153056ca98d55ab44b8cdb562/docs/explanation/mcp-2026-07-28/01-stateless-protocol.md)
- [webman-mcp](https://github.com/tinywan/webman-mcp) · [Neuron-AI](https://github.com/neuron-core/neuron-ai) · [Neuron MCP Connector 文档](https://docs.neuron-ai.dev/agent/mcp-connector)
