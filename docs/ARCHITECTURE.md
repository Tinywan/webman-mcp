# Architecture

## Request Pipeline

1. Webman selects a configured Server route.
2. The transport enforces POST, media negotiation, body size, and Origin policy.
3. The selected Server authenticator creates a request-scoped `Principal`; static Bearer configuration
   retains digests only.
4. Rate and concurrency admission use a Principal-scoped request descriptor before protocol parsing.
5. `ProtocolParser` parses one JSON-RPC request or notification and its required metadata.
6. Header mirrors and optional method-scoped idempotency records are validated.
7. `NativeProtocolDriver` routes a supported Server, Tool, Resource, Prompt, Completion, or Subscription method.
8. Capability runtimes apply independent authorization, validation, pagination, limits, and fresh handler/provider resolution.
9. The response mapper enforces deadline and serialized byte limits, then emits one JSON envelope, an empty HTTP 202 response, or a bounded
   `subscriptions/listen` event stream.
10. Request leases are released in all outcomes; redacted audit and telemetry adapters observe each boundary.

## State Model

`ServerDefinition`, capability definitions, and `ServerRegistry` are immutable Worker-level definitions. A
`Principal`, `ExecutionContext`, deadline, arguments, and handler instance belong to one request. Tool
Resource, Prompt, Completion, and Subscription providers are resolved through the Webman container
for each invocation and are never cached by the SDK. Cancellation and progress objects are scoped to
one request; the default cancellation coordinator is process-local and replaceable.

## Boundaries

The public extension contracts include protocol, Tool, Resource, Prompt, Completion, Subscription,
notification, cancellation, progress, resolver, and authorizer
authentication, and Tool authorization boundaries. They use SDK DTOs and PHP built-in types. Opis is
contained inside Tool Schema validation, and Webman request/response objects are contained inside the
transport adapter.

Rate limiter, concurrency limiter, idempotency store, audit sink, and telemetry implementations are
Worker-level services by design. Their decisions, leases, replay identity, trace ID, deadline, and
Principal remain request-scoped. Distributed deployments must provide shared implementations where a
process-local boundary is insufficient.

Publishable Webman configuration lives under `src/config/plugin/tinywan/webman-mcp` in the package.
The standard Webman installer and `mcp:install` publish it to the application's
`config/plugin/tinywan/webman-mcp` directory without overwriting existing files.

Production dispatch uses `StreamResult` only for `subscriptions/listen`. Webman maps it to a chunked
POST response without session IDs, replay IDs, or a legacy GET/DELETE route.
