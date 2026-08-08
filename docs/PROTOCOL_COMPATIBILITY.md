# Protocol Compatibility

## Baseline

- Protocol version: `2026-07-28`
- Official schema commit: `271ecc9accafdd9b83a3c869fa67c22953b2af80`
- Schema SHA-256: `ef70b61f99b6d2e5e3b46863822eab08dff6a45bedc7a08914e0e5b133f40203`
- Complete offline schema: `resources/schema/2026-07-28-schema.json` (`181474` bytes)
- Request IDs: string or integer only

The complete official schema is pinned byte-for-byte and verified by offline tests. Runtime protocol
handling does not fetch or evaluate a Schema over the network.

## Supported Methods

- `server/discover`
- `tools/list`
- `tools/call`
- `resources/list`
- `resources/read`
- `resources/templates/list`
- `prompts/list`
- `prompts/get`
- `completion/complete`
- `subscriptions/listen`
- `notifications/cancelled`

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
- The official `MCP-Protocol-Version` and `Mcp-Method` routing Headers are required; `Mcp-Name` is
  required for `tools/call`. Their values must match the JSON-RPC body.
- Notifications return HTTP 202 with an empty body.
- Configured governance applies finite deadlines and serialized byte limits to JSON and event-stream responses.
- Optional rate, concurrency, and method-scoped idempotency controls do not add protocol methods or session state.
- Incoming `Mcp-Session-Id` and `Last-Event-ID` are ignored and never echoed.
- A successful `subscriptions/listen` POST emits `text/event-stream`; all other successful requests
  remain JSON and notifications remain empty HTTP 202 responses.

## Breaking Legacy Boundary

Version 0.1 intentionally does not support `initialize`, legacy GET/DELETE SSE transport, protocol probing,
downgrade, session creation/resumption, or legacy clients that require those behaviors.

## Client Compatibility

Clients, including Codex, must support MCP `2026-07-28`. Conformance tests cover a stateless
Codex-shaped request with per-request client information and the official routing Headers, but the SDK
does not implement or emulate Client behavior.

## Deferred Capabilities

Client APIs, Tasks, MRTR, durable subscription replay, an OAuth authorization server, and built-in
JWT/JWKS verification are outside v0.1. JWT/JWKS adapters remain available through the verifier contract.
