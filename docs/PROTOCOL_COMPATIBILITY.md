# Protocol Compatibility

## Baseline

- Protocol version: `2026-07-28`
- Official schema commit: `271ecc9accafdd9b83a3c869fa67c22953b2af80`
- Schema SHA-256: `ef70b61f99b6d2e5e3b46863822eab08dff6a45bedc7a08914e0e5b133f40203`
- Request IDs: string or integer only

The runtime and tests use the versioned local baseline metadata and do not fetch a Schema over the
network.

## Supported Methods

- `server/discover`
- `tools/list`
- `tools/call`

Every other method, including `initialize`, returns JSON-RPC method-not-found (`-32601`). The SDK does
not probe or negotiate another protocol version.

## Errors

| Error | Code | HTTP status |
| --- | ---: | ---: |
| Parse error | `-32700` | 400 |
| Invalid request | `-32600` | 400 |
| Method not found | `-32601` | 404 |
| Invalid params | `-32602` | 400 |
| Internal error | `-32603` | 500 |
| Header mismatch | `-32020` | 400 |
| Unsupported protocol version | `-32022` | 400 |

Errors retain a valid request ID when it can be identified safely and otherwise omit `id`.

## HTTP Contract

- Each Server has an independent POST endpoint.
- `Content-Type` must be `application/json`.
- `Accept` must include both `application/json` and `text/event-stream`.
- `MCP-Protocol-Version` and `Mcp-Method` are required; `Mcp-Name` is required for `tools/call`.
- Notifications return HTTP 202 with an empty body.
- Incoming `Mcp-Session-Id` and `Last-Event-ID` are ignored and never echoed.
- No v0.1 response emits SSE.

## Breaking Legacy Boundary

Version 0.1 intentionally does not support `initialize`, legacy HTTP/SSE transport, protocol probing,
downgrade, session creation/resumption, or legacy clients that require those behaviors.

## Deferred Capabilities

Client APIs, Resources, Prompts, Tasks, MRTR, subscriptions, SSE output, OAuth, JWT/JWKS, rate
limiting, idempotency, and full audit facilities are outside v0.1.
