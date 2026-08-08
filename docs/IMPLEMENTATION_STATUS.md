# Implementation Status

## v0.1 Complete

- Composer library boundary, strict public DTOs, Pest, and Mago quality gate
- Pinned MCP `2026-07-28` protocol parsing and deterministic errors
- Immutable multi-Server registry and deny-by-default security policies
- Tool listing/calling, Opis Schema validation, local references, and sanitized failures
- Resource and Resource Template listing, opaque pagination, authorized reads, and bounded content
- Prompt listing/rendering and bounded Prompt/Resource argument Completion
- Typed Server notifications, authorized bounded Subscriptions, request progress, and cancellation
- Digest-only static Bearer authentication with a pluggable verifier boundary
- Principal-scoped rate/concurrency admission, explicit idempotency, deadlines, and response limits
- Redacted lifecycle audit, telemetry, null defaults, and Webman-friendly adapters
- Stateless Streamable HTTP POST transport, bounded event streams, and Webman chunked response adapter
- Publishable plugin config and five Webman Console commands
- Calculator Tool, Library Resource, Assistant Prompt, and Status Subscription examples with protocol tests

## Intentional Limits

The release has no Client, Tasks, MRTR, durable subscription replay, legacy GET/DELETE event
transport, session support, protocol downgrade, OAuth authorization server, built-in JWT/JWKS
verification, or a mandated distributed limiter/store/metrics vendor. These remain application concerns.
