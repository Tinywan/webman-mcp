# Webman MCP SDK v0.1 现代协议实施计划

## Summary

实现 `tinywan/webman-mcp v0.1`，PHP namespace 使用 `Tinywan\Mcp`。仅支持 MCP `2026-07-28` 现代无状态协议，不兼容 `initialize`、`Mcp-Session-Id`、旧 HTTP/SSE 或旧版客户端。

协议基线固定到官方 Schema 提交 `271ecc9accafdd9b83a3c869fa67c22953b2af80`。v0.1 包含多 Server、Streamable HTTP POST、`server/discover`、`tools/list`、`tools/call`、安全扩展接口和 Webman 脚手架；Client、Resources、Prompts、Tasks、MRTR、订阅和 SSE 输出延后。

## Implementation

1. 创建 OpenSpec change `implement-modern-mcp-v0-1`，生成 proposal、design、能力 specs 和可验收 tasks。
2. 初始化 Composer 库：
   - 包名 `tinywan/webman-mcp`
   - PSR-4：`Tinywan\Mcp\` 映射至 `src/`
   - PHP `^8.2`
   - 所有一方 PHP 文件必须在 `<?php` 后声明 `declare(strict_types=1);`
   - `workerman/webman-framework ^2.1`
   - `opis/json-schema ^2.4`
   - `pestphp/pest ^3.8`（底层使用 PHPUnit 11）
   - `carthage-software/mago ^1.45`，统一负责 lint、格式检查和静态分析
   - 不依赖 `mcp/sdk`
3. 建立原生现代协议层：
   - 严格解析单个 JSON-RPC request/notification，拒绝 batch。
   - `RequestId` 严格遵循固定 Schema，仅支持 `string|int`，不接受 `float` 或 `null`。
   - 每个请求必须包含协议版本和 client capabilities 元数据。
   - 实现 `HeaderMismatch -32020`、`UnsupportedProtocolVersion -32022` 及标准 JSON-RPC 错误。
4. 实现 Webman HTTP Transport：
   - 每个 Server 暴露独立 POST endpoint。
   - 校验 `Content-Type`、双类型 `Accept`、2 MiB body 上限和 Origin。
   - 校验协议版本、`Mcp-Method`、`Mcp-Name` 与 Body 一致。
   - 支持 Base64 sentinel 和 `Mcp-Param-*`/`x-mcp-header`。
   - Notification 返回 202；请求返回 JSON；GET/DELETE 返回 405。
   - v0.1 不产生 SSE。
5. 实现不可变的多 Server Registry：
   - 每个 Server 配置唯一 ID、路径、身份信息、Tool 集、认证器、授权器和 Origin。
   - Worker 启动时检测重复 Server、路径和 Tool 名称。
   - 只缓存 Tool definition，不缓存 handler 或请求上下文。
6. 实现 Tool：
   - `tools/list` 只返回当前 Principal 可见的 Tool。
   - `tools/call` 校验名称、参数对象、JSON Schema 2020-12 和权限。
   - 仅允许本地 `$ref`/`$defs`，禁止网络 Schema 解析。
   - 定义 outputSchema 时校验 structuredContent。
   - 协议错误返回 JSON-RPC error；业务失败返回 `isError: true`。
   - 成功结果使用 `resultType: "complete"`。
7. 集成 Webman 插件：
   - 发布 `app.php`、`servers.php` 和路由配置。
   - 提供 `mcp:install`、`make:mcp-server`、`make:mcp-tool`、`mcp:list`、`mcp:inspect`。
   - 生成器不得覆盖文件，生成的 PHP 文件必须包含 `declare(strict_types=1);`，并通过 Mago lint、format check 和 analyze。
   - 提供 Calculator Server 示例和快速开始。
8. 补齐 AGENTS、架构、协议兼容、安全和实施状态文档。

## Public APIs

所有公共类型位于 `Tinywan\Mcp`：

```php
namespace Tinywan\Mcp\Contracts;

interface ProtocolDriverInterface
{
    public function dispatch(
        ProtocolRequest $request,
        ExecutionContext $context
    ): ProtocolDispatchResult;
}

interface ToolInterface
{
    public function definition(): ToolDefinition;

    public function call(
        ToolCall $call,
        ExecutionContext $context
    ): ToolResult;
}

interface AuthenticatorInterface
{
    public function authenticate(HttpRequestContext $request): Principal;
}

interface AuthorizerInterface
{
    public function canList(Principal $principal, ToolDefinition $tool): bool;

    public function canCall(Principal $principal, ToolDefinition $tool): bool;
}
```

`ExecutionContext` 是只读对象，包含 Principal、Trace ID、协议版本、clientInfo、clientCapabilities 和 deadline。

默认使用 `DenyAllAuthenticator`；只有显式配置 `AllowAnonymousAuthenticator` 才允许匿名访问。

`ProtocolDispatchResult` 预留 `JsonResult`、`AcceptedResult`、`StreamResult`，v0.1 只产生前两种。

## Test Plan

- 单元测试统一使用 Pest DSL，并通过 `tests/Pest.php` 维护公共 bootstrap、expectations 和 helpers。
- Header、错误码、Schema 与协议版本矩阵使用 Pest datasets，避免重复测试代码。
- DTO 与协议：ID 类型、必需元数据、非法 JSON、batch、notification、未知方法和错误映射。
- HTTP：Header 缺失/冲突、大小写、Base64、非 ASCII 名称、参数镜像、Body 上限、Origin 和 Method。
- Discovery：版本、serverInfo、capabilities、instructions 和认证可见性。
- Tools：重复名称、列表、调用、输入/输出 Schema、未知 Tool、未授权、业务异常和脱敏。
- 常驻内存：用户隔离、同 Worker 并发交错、多 Worker 无隐式状态、handler 不跨请求缓存。
- CLI：安装、生成、拒绝覆盖、配置验证和多 Server 列表。
- 使用 Pest 架构测试扫描 `src/`、`tests/`、`examples/` 和项目 PHP 配置，缺少 `declare(strict_types=1);` 时失败；排除 `vendor/`。
- 使用版本化的 `mago.toml` 检查 `src/`、`tests/` 和 `examples/`，不使用 baseline 隐藏新增问题。
- `composer test` 运行 `vendor/bin/pest`。
- `composer lint` 运行 `vendor/bin/mago lint`，`composer analyse` 运行 `vendor/bin/mago analyze`。
- `composer format:check` 运行 `vendor/bin/mago format --check`，`composer format` 用于显式格式化。
- `composer check` 依次执行 format check、lint、analyze 和 test，全部通过才允许完成任务。

## Assumptions

- Composer 包名固定为 `tinywan/webman-mcp`。
- PHP 根 namespace 固定为 `Tinywan\Mcp`。
- 所有项目维护的 PHP 源码、测试、示例、命令和配置均启用严格类型；第三方依赖不受此约束。
- Webman 依赖使用 `workerman/webman-framework`。
- 测试入口固定为 Pest 3.x；为保持 PHP 8.2 支持，不升级到要求 PHP 8.3/8.4 的 Pest 4/5。
- 代码质量工具固定为 Mago 1.x，不同时引入 PHPStan、Psalm、PHP-CS-Fixer 或 PHP_CodeSniffer。
- 默认拒绝匿名；OAuth、JWT/JWKS、限流、幂等和完整审计延后。
- 不支持旧协议探测或降级。
- deadline 由 Tool 协作检查，v0.1 不承诺强制中断。
- 不创建 Git commit，除非后续明确要求。
