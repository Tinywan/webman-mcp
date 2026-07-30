## Why

Webman 目前缺少一个面向常驻内存运行模型、可安全承载多个 MCP Server 的现代 PHP SDK。项目需要以固定的 MCP `2026-07-28` Schema 基线交付一个范围清晰的 v0.1，为后续 Client、Resources、Prompts、Tasks 和流式能力建立稳定边界。

## What Changes

- 将仓库初始化为 `tinywan/webman-mcp` Composer 库，要求 PHP 8.2、`Tinywan\Mcp` PSR-4 命名空间、严格类型、Pest 3 和 Mago 质量门禁，且不依赖 `mcp/sdk`。
- 新增原生 MCP `2026-07-28` 无状态 JSON-RPC 协议层，支持 `server/discover`、`tools/list`、`tools/call`、协议元数据和确定的错误映射。
- 新增 Webman Streamable HTTP POST transport，执行内容协商、Header/Body 一致性、Origin、大小限制和扩展参数校验。
- 新增不可变的多 Server registry、身份认证与授权扩展点，以及常驻内存环境下的请求上下文隔离。
- 新增 Tool 定义、可见性过滤、调用、JSON Schema 2020-12 输入/输出验证和业务错误建模。
- 新增 Webman 配置发布、路由、安装/生成/检查命令、Calculator 示例和快速开始文档。
- **BREAKING** 仅支持 MCP `2026-07-28`；不接受 `initialize`、旧 HTTP/SSE transport、旧版客户端、协议探测或降级。传入的旧 `Mcp-Session-Id`/`Last-Event-ID` 仅按现代规范忽略，Server 不创建、使用或回显 session。
- v0.1 明确不交付 Client、Resources、Prompts、Tasks、MRTR、订阅、SSE 输出、OAuth、JWT/JWKS、限流、幂等和完整审计。

## Capabilities

### New Capabilities

- `sdk-foundation`: Composer 包边界、公共 API、严格类型约束、依赖与统一质量门禁。
- `modern-protocol`: MCP `2026-07-28` 请求/通知解析、请求元数据、discovery、dispatch 结果和 JSON-RPC 错误语义。
- `streamable-http-transport`: Webman HTTP endpoint、方法与内容协商、Header 镜像、Origin、Body 限制、Base64 扩展和响应映射。
- `multi-server-registry`: 多 Server 配置、启动期唯一性校验、不可变 registry、安全默认值与请求级上下文隔离。
- `tool-runtime`: Tool 列表与调用授权、JSON Schema 2020-12 验证、本地引用约束、结果和业务失败语义。
- `webman-integration`: 可发布配置与路由、CLI 安装和生成器、运行时检查、Calculator 示例及维护文档。

### Modified Capabilities

无。

## Impact

- 新增和调整 `src/`（含可发布的 `src/config/`）、`tests/`、`examples/`、文档、Composer/Mago/Pest 配置及 Webman 插件入口。
- 公共 API 位于 `Tinywan\Mcp`，包括 protocol driver、Tool、认证、授权、DTO、registry 和 transport 类型。
- 运行时依赖 `workerman/webman-framework ^2.1` 与 `opis/json-schema ^2.4`；开发依赖 Pest 3.8 和 Mago 1.45。
- HTTP 集成方必须发送现代协议元数据并适配无 session、无 SSE 的 POST-only 行为。
- 安全默认值为拒绝匿名访问；只有显式配置 `AllowAnonymousAuthenticator` 才允许匿名调用。
