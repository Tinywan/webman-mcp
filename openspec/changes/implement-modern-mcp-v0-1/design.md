## Context

仓库当前只有早期 Composer 元数据和空的 `src/`，尚未形成可用 SDK。目标运行环境是 PHP 8.2、Webman 2.1 和 Workerman 常驻内存多进程模型；这要求配置可在 Worker 启动时复用，但 Principal、协议元数据、deadline、handler 实例和请求数据不得泄漏到后续请求。

协议行为以 MCP `2026-07-28` 官方 Schema 提交 `271ecc9accafdd9b83a3c869fa67c22953b2af80` 为唯一基线。仓库中的早期技术方案包含 `mcp/sdk`、Client 和其他未来能力的探索内容，若与 `IMPLEMENTATION_PLAN.md` 或固定 Schema 冲突，以本 change 为准。

主要使用者是将 MCP Server 接入 Webman 的库维护者和应用开发者。对外边界包括 Webman HTTP endpoint、Composer 公共类型、Server/Tool 配置和 CLI；对内边界包括 transport、protocol driver、registry、schema validator 与请求级 execution context。

## Goals / Non-Goals

**Goals:**

- 提供仅支持 MCP `2026-07-28` 的原生、无状态 Server 协议实现。
- 在同一 Webman 应用中安全承载多个独立 Server endpoint。
- 提供 `server/discover`、`tools/list` 和 `tools/call` 的完整请求链路。
- 使认证、授权、Tool 与 transport 通过稳定的 `Tinywan\Mcp` 公共接口解耦。
- 通过不可变配置、请求级上下文和可验证的缓存规则适配常驻内存 Worker。
- 使用 Pest 和 Mago 建立可重复执行的格式、lint、静态分析和测试门禁。

**Non-Goals:**

- 不实现 Client、Resources、Prompts、Tasks、MRTR、订阅或 SSE 输出。
- 不支持 `initialize`、旧 HTTP/SSE transport、旧客户端、版本探测或协议降级；收到旧 `Mcp-Session-Id`/`Last-Event-ID` 时仅忽略，且不创建、使用或回显 session。
- 不内置 OAuth、JWT/JWKS、限流、幂等、完整审计或强制中断 Tool 的机制。
- 不用 `mcp/sdk` 的类型或运行时行为作为 v0.1 协议实现。

## Decisions

### 1. 以固定 Schema 生成协议模型和兼容性测试

把官方提交 `271ecc9accafdd9b83a3c869fa67c22953b2af80` 记录到版本化的 protocol fixture/文档中，并围绕它实现 DTO、解析器和兼容矩阵。运行时不联网获取 Schema；升级协议必须通过后续 OpenSpec change 完成。

选择固定提交而不是跟随远程 `main`，是为了让错误码、字段必需性和测试结果可复现。选择原生 DTO/driver 而不是 `mcp/sdk`，是因为 v0.1 只接受现代无状态协议，且必须避免旧协议兼容逻辑进入公共 API。

### 2. 使用分层的单向请求管线

请求按以下顺序处理：Webman route 选择 Server；transport 校验 HTTP method、Origin、媒体类型、大小和 Header；authenticator 产生 Principal；parser 创建 `ProtocolRequest`；driver 根据 Server registry dispatch；authorizer 过滤或批准 Tool；response mapper 生成 Webman response。

HTTP 问题在 transport 层终止，JSON-RPC/协议问题映射为协议错误，Tool 业务失败映射为成功的 JSON-RPC envelope 内 `isError: true`。这样可避免 handler 同时理解 Webman request、JSON-RPC 和业务错误。备选方案是在 Controller 中集中完成全部工作，但会耦合测试边界并增加上下文泄漏风险。

### 3. 将静态定义与请求状态严格分开

`ServerDefinition`、`ToolDefinition` 和构建完成的 registry 使用只读对象，在 Worker 启动时完成重复 Server ID、路径和 Tool 名称校验。仅缓存不可变 Tool definition 及可安全复用的已编译 Schema 信息；Tool handler 每次调用都从 Webman 容器解析，且不缓存 `ExecutionContext`、Principal 或 transport request。

`ExecutionContext` 为请求级只读值对象，包含 Principal、trace ID、协议版本、clientInfo、clientCapabilities 和 deadline，并作为显式参数传给 driver、authorizer 和 Tool。备选的全局/current-context 容器访问更方便，但在协程或交错请求中无法证明隔离性，因此不采用。

### 4. 公共 API 使用领域 DTO 和最小扩展接口

公共类型全部位于 `Tinywan\Mcp`。核心扩展点固定为 `ProtocolDriverInterface`、`ToolInterface`、`AuthenticatorInterface` 和 `AuthorizerInterface`；参数及返回值使用库自身的只读 DTO。`ProtocolDispatchResult` 是 `JsonResult`、`AcceptedResult`、`StreamResult` 的封闭结果族，v0.1 保留 `StreamResult` 类型但生产路径不得返回它。

这避免 Webman、Opis 或未来协议实现的具体类型进入用户 Tool。直接暴露框架 request 或 schema validator 虽减少转换代码，但会锁死依赖并扩大兼容面。

### 5. HTTP transport 先做边界校验，再解析业务消息

每个 Server 配置独立 POST 路径。transport 对 Header 名称大小写不敏感，并在解析/dispatch 前校验 `Content-Type`、同时声明 JSON 与 event-stream 的 `Accept`、默认 2 MiB 上限、Origin allowlist、协议版本，以及 `Mcp-Method`/`Mcp-Name` 与 Body 的一致性。`Mcp-Param-*` 和 `x-mcp-header` 扩展统一进入受控解码器；Base64 sentinel 仅用于安全表达非 ASCII 或非 Header-safe 值，非法编码直接拒绝。

notification 返回 202 且无 JSON-RPC body；带 ID 的请求返回 JSON。GET/DELETE 返回 405；传入的 `Mcp-Session-Id`/`Last-Event-ID` 被忽略且不回显；所有 v0.1 路径都不创建 session、不返回 SSE。仍要求双类型 `Accept` 是为了遵循现代 Streamable HTTP 请求协商约定，不代表 Server 必须产生流式响应。

### 6. Schema 验证禁止任何网络解析

使用 `opis/json-schema ^2.4` 校验 Tool input 和可选 output，方言固定为 JSON Schema 2020-12。注册阶段检查 Schema 结构及引用，只允许同一 Schema 文档内的 `$ref`/`$defs`；URI、文件和其他外部引用均失败。定义了 `outputSchema` 时，成功或业务失败结果中的 `structuredContent` 都必须符合它。

禁用网络解析牺牲了跨文件 Schema 复用，但消除了 SSRF、不可复现构建和运行时网络依赖。共享结构应在 v0.1 内通过本地 `$defs` 表达。

### 7. 默认拒绝认证，授权同时作用于 list 和 call

未配置认证器时使用 `DenyAllAuthenticator`。匿名访问只有在 Server 显式配置 `AllowAnonymousAuthenticator` 时启用。`AuthorizerInterface::canList` 决定 discovery/list 可见性，`canCall` 在调用前再次检查，防止通过已知 Tool 名称绕过列表过滤。异常和拒绝响应不得泄露密钥、原始堆栈或敏感 Principal 属性。

选择双重授权检查而不是把列表视为授权票据，是因为请求无 session，且权限可能按每个请求的 Principal 变化。

### 8. Webman 集成以可发布配置和显式 CLI 为边界

包提供 `app.php`、`servers.php` 和路由配置模板，并通过 `mcp:install` 发布。`make:mcp-server` 与 `make:mcp-tool` 只生成严格类型 PHP 文件，目标存在时失败；`mcp:list` 展示解析后的 Server/Tool，`mcp:inspect` 执行配置、Schema 和唯一性诊断。Calculator 示例使用匿名认证仅作演示，并在文档中标明生产环境需要显式认证策略。

### 9. 单一质量工具链

Pest 3.x 是唯一测试入口，公共 bootstrap、expectations、helpers 放在 `tests/Pest.php`，矩阵场景使用 datasets。Mago 1.x 独立承担 format check、lint 和 analyze，不并行引入其他代码质量工具。`composer check` 固定依次执行 format check、lint、analyze、test；版本化 `mago.toml` 不使用 baseline。

架构测试扫描项目维护的 PHP 文件并要求 `<?php` 后声明 `declare(strict_types=1);`，排除 `vendor/`。这把严格类型和生成器约束变为持续可验收行为。

## Risks / Trade-offs

- [协议 Schema 尚新，字段可能在后续提交变化] → 固定 commit、保存兼容性文档和 fixture，任何升级单独提案并运行完整矩阵。
- [要求双类型 `Accept` 但 v0.1 不输出 SSE，可能使集成方困惑] → 在快速开始和错误响应中明确协商要求与无 SSE 的版本边界。
- [Worker 级缓存引入跨请求状态泄漏] → 缓存白名单只包含不可变 definition/编译 Schema，并用交错请求及多 Worker 测试验证。
- [用户提供的 Schema 触发网络访问或复杂度攻击] → 注册期拒绝外部引用、限制请求体、使用 Opis 的本地解析路径，并为深层/非法 Schema 添加负向测试。
- [默认拒绝认证降低开箱即用体验] → Calculator 示例显式启用匿名认证；安装后的默认生产配置保持 deny-all。
- [不依赖官方 SDK 增加协议维护成本] → 将实现隔离在 driver 和 DTO 边界，并以固定官方 Schema 的兼容测试约束差异。
- [Tool deadline 不能强制中断执行] → 将 deadline 显式传入上下文并要求 Tool 协作检查，文档明确 v0.1 保证范围。

## Migration Plan

1. 更新 Composer 元数据、依赖、autoload 和质量脚本，建立最小可运行测试基线。
2. 先实现 DTO、错误模型和协议 fixture，再实现 registry、认证/授权与 schema validator。
3. 实现三个协议方法及 transport，将 Webman endpoint 接入不可变 registry。
4. 发布配置、CLI、Calculator 示例和文档，运行 `composer check` 作为交付门禁。
5. 这是首个可用版本，不迁移历史 runtime 数据。现有试验性代码若与公共 API 冲突，应在发布 v0.1 前替换；回滚方式是恢复上一 Composer 版本和应用配置，不保留协议降级路径。

## Open Questions

- 无阻塞问题。具体 wire 字段、枚举和 error data 形状在实现时必须逐项采用固定官方 Schema，而不是从本设计文字推断。
