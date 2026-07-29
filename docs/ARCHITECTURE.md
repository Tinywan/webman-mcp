# Architecture

## Request Pipeline

1. Webman selects a configured Server route.
2. The transport enforces POST, media negotiation, body size, and Origin policy.
3. The selected Server authenticator creates a request-scoped `Principal`.
4. `ProtocolParser` parses one JSON-RPC request or notification and its required metadata.
5. Header mirrors are compared with the parsed protocol message and Tool arguments.
6. `NativeProtocolDriver` routes one of the three supported methods.
7. `ToolRuntime` applies independent list/call authorization, Schema validation, and fresh handler resolution.
8. The response mapper emits one JSON envelope or an empty HTTP 202 notification response.

## State Model

`ServerDefinition`, `ToolDefinition`, and `ServerRegistry` are immutable Worker-level definitions. A
`Principal`, `ExecutionContext`, deadline, arguments, and handler instance belong to one request. Tool
handlers are resolved through the Webman container for each invocation and are never cached by the SDK.

## Boundaries

The public extension contracts are `ProtocolDriverInterface`, `ToolInterface`,
`AuthenticatorInterface`, and `AuthorizerInterface`. They use SDK DTOs and PHP built-in types. Opis is
contained inside Tool Schema validation, and Webman request/response objects are contained inside the
transport adapter.

The v0.1 result family reserves `StreamResult`, but production dispatch returns only `JsonResult` or
`AcceptedResult`.
