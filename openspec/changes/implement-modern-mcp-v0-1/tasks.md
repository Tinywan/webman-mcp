## 1. 项目与质量基线

- [x] 1.1 更新 `composer.json` 的包类型、PHP `^8.2`、PSR-4、Webman `^2.1`、Opis `^2.4`、Pest `^3.8` 和 Mago `^1.45` 依赖，并确认依赖图不包含 `mcp/sdk`
- [x] 1.2 建立 `src/`（含可发布的 `src/config/`）、`tests/`、`examples/` 和 `docs/` 的模块目录与严格类型 PHP 入口
- [x] 1.3 配置 `tests/Pest.php`、测试套件和公共 helpers/expectations，确认最小 Pest 测试可运行
- [x] 1.4 创建版本化 `mago.toml` 和 `format:check`、`format`、`lint`、`analyse`、`test`、`check` Composer scripts，且不使用 baseline
- [x] 1.5 添加严格类型架构测试，扫描项目维护的源码、测试、示例、命令和 PHP 配置并排除 `vendor/`

## 2. 协议基线与公共 DTO

- [x] 2.1 固化官方 Schema 提交 `271ecc9accafdd9b83a3c869fa67c22953b2af80` 的本地 fixture、来源和校验信息，确保测试与运行时无需联网
- [x] 2.2 实现固定 Schema 的 `RequestId` 值对象及 string、integer 的保真序列化，并为 float、null、boolean 和容器值添加拒绝测试
- [x] 2.3 实现只读 `ProtocolRequest`、clientInfo、clientCapabilities、Principal、deadline 和 `ExecutionContext` DTO
- [x] 2.4 实现 `ProtocolDriverInterface`、`ToolInterface`、`AuthenticatorInterface`、`AuthorizerInterface` 及其领域 DTO 签名测试
- [x] 2.5 实现 `JsonResult`、`AcceptedResult`、预留 `StreamResult` 的 dispatch 结果族，并测试 v0.1 生产路径限制

## 3. JSON-RPC 解析与错误模型

- [x] 3.1 实现单消息 JSON-RPC 2.0 解码器，区分 request 与 notification，并拒绝非法 JSON、非对象顶层值和 batch
- [x] 3.2 根据固定 Schema 校验协议版本、clientInfo 和 clientCapabilities 等每请求必需元数据
- [x] 3.3 实现标准 parse、invalid-request、method-not-found、invalid-params、internal 错误及 ID 保留规则
- [x] 3.4 实现 `HeaderMismatch -32020` 与 `UnsupportedProtocolVersion -32022` 错误构造和安全的 error data
- [x] 3.5 使用 Pest datasets 覆盖 ID、必需元数据、协议版本、非法 JSON、batch、notification 和错误响应矩阵

## 4. 多 Server Registry 与安全上下文

- [x] 4.1 实现只读 `ServerDefinition`、`ToolDefinition`、Origin policy 及配置到定义的规范化转换
- [x] 4.2 实现不可变 `ServerRegistry`，在 Worker 服务流量前拒绝重复 Server ID、规范化路径和同 Server Tool 名称
- [x] 4.3 实现 `DenyAllAuthenticator`、显式 `AllowAnonymousAuthenticator` 和默认认证器装配规则
- [x] 4.4 实现授权器装配以及 `canList`、`canCall` 两个独立授权检查边界
- [x] 4.5 实现每次调用从 Webman 容器解析 handler 的机制，并限制 Worker 缓存为不可变 Tool definition/本地 Schema 数据
- [x] 4.6 添加同 Worker 交错请求、多 Principal、handler 非缓存和多 Worker 等价 registry 的隔离测试

## 5. Tool Schema 与调用运行时

- [x] 5.1 封装 Opis JSON Schema 2020-12 校验器，注册期验证 Tool input/output Schema 并只允许 fragment-only 本地 `$ref`/`$defs`
- [x] 5.2 添加远程 URL、文件引用、非法本地引用和合法 `$defs` 的测试，确认拒绝路径不执行网络或文件解析
- [x] 5.3 实现 `ToolCall`、`ToolResult`、content 与 structuredContent DTO，固定成功 `resultType: "complete"` 和业务失败 `isError: true` 语义
- [x] 5.4 实现 Principal 过滤的 `tools/list`，确认隐藏 Tool 的名称、描述和 Schema 均不泄露
- [x] 5.5 实现 `tools/call` 的精确名称解析、独立调用授权、arguments 对象检查和 inputSchema 验证
- [x] 5.6 在响应前验证声明了 outputSchema 的 structuredContent，并将契约违反转换为脱敏协议错误
- [x] 5.7 将 handler 异常和 deadline 失败映射为带 trace ID 的脱敏错误，不向客户端暴露堆栈、凭据、Principal 敏感字段或原始异常消息
- [x] 5.8 使用 Pest datasets 覆盖 Tool 重名、可见性、未知/未授权调用、输入/输出 Schema、成功、业务失败、异常和脱敏

## 6. Discovery 与协议 Driver

- [x] 6.1 实现 `server/discover`，按当前 Principal 返回固定版本、Server identity、capabilities、可见 instructions 和可用 Tool 能力
- [x] 6.2 实现仅路由 `server/discover`、`tools/list`、`tools/call` 的原生 protocol driver，并让 `initialize` 等所有其他方法返回 method-not-found
- [x] 6.3 将 request dispatch 映射为 `JsonResult`、notification dispatch 映射为 `AcceptedResult`，并证明任何 v0.1 分支都不产生 `StreamResult`
- [x] 6.4 添加 discovery 身份/能力/认证可见性、未知方法、无 session 和无协议降级的兼容测试

## 7. Webman Streamable HTTP Transport

- [x] 7.1 实现每 Server 独立路由选择与 POST-only method guard，为 GET、DELETE 和其他方法返回带 `Allow: POST` 的 405
- [x] 7.2 实现 JSON `Content-Type` 与 JSON/event-stream 双类型 `Accept` 校验，按 HTTP 规则处理 Header 名和 token 大小写
- [x] 7.3 实现可配置且默认 2 MiB 的 body 限制和 dispatch 前 Origin allowlist 校验
- [x] 7.4 实现协议版本、`Mcp-Method`、`Mcp-Name` 与 Body 镜像校验，并将缺失或冲突映射为 `HeaderMismatch -32020`
- [x] 7.5 实现 `Mcp-Param-*`、`x-mcp-header` 和固定 Schema Base64 sentinel 解码，保真处理非 ASCII 并拒绝非法编码/冲突
- [x] 7.6 实现 Webman request/authentication/protocol 管线和 response mapper：request 返回单个 JSON envelope，notification 返回空 body 202
- [x] 7.7 忽略且不回显 `Mcp-Session-Id`/`Last-Event-ID`，拒绝旧 GET/DELETE transport 行为，并添加测试证明所有响应路径均不创建 session 或输出 SSE
- [x] 7.8 使用 Pest datasets 覆盖 method、Header 缺失/冲突/大小写、媒体类型、Base64、非 ASCII、参数镜像、body 上限和 Origin

## 8. Webman 插件与 CLI

- [x] 8.1 创建可发布 `app.php`、`servers.php` 和路由配置，以 deny-all 为默认并支持声明多个 Server
- [x] 8.2 实现 Webman 启动装配，在 Worker 接受请求前构建并验证 registry、注册各 Server 路由
- [x] 8.3 实现幂等且不覆盖已有应用文件的 `mcp:install`，为已存在目标返回清晰冲突信息
- [x] 8.4 实现 `make:mcp-server` 与 `make:mcp-tool` 模板和命令，生成严格类型、符合公共 contract 且通过 Mago 的 PHP，并拒绝覆盖
- [x] 8.5 实现 `mcp:list` 输出规范化 Server/Tool 拓扑，且不泄露敏感配置
- [x] 8.6 实现 `mcp:inspect` 对配置、唯一性、Tool definition 和 Schema 的离线诊断及非零失败退出码
- [x] 8.7 添加安装幂等性、生成器拒绝覆盖/严格类型、配置验证、list/inspect 和多 Server 路由的 CLI 集成测试

## 9. 示例与文档

- [x] 9.1 创建显式使用 `AllowAnonymousAuthenticator` 的 Calculator Server 和 schema-validated Calculator Tool 示例
- [x] 9.2 添加 Calculator 的 discovery、tools/list、有效调用和无效参数端到端测试
- [x] 9.3 编写快速开始，准确展示现代 Header、双类型 `Accept`、无 session、notification 202 和生产认证提示
- [x] 9.4 创建或更新 `AGENTS.md`、`docs/ARCHITECTURE.md`、`docs/PROTOCOL_COMPATIBILITY.md`、`docs/SECURITY.md` 和 `docs/IMPLEMENTATION_STATUS.md`
- [x] 9.5 在兼容文档中列明固定 Schema commit、三个支持方法、错误码、BREAKING 旧协议边界和全部 v0.1 延后能力

## 10. 最终验收

- [x] 10.1 运行 Composer 依赖与 autoload 校验，确认 PHP 8.2 约束、公共 namespace 和无 `mcp/sdk` 依赖
- [x] 10.2 运行固定 Schema 兼容矩阵以及 DTO、HTTP、discovery、Tool、常驻内存和 CLI 全部 Pest 测试
- [x] 10.3 运行 `composer check` 并修复所有 format、lint、analyze 和 test 失败，不引入 baseline 或第二套质量工具
- [x] 10.4 对照六个 capability specs 逐项核验实现与文档，确认无 Client、Resources、Prompts、Tasks、MRTR、订阅或 SSE 输出越界
