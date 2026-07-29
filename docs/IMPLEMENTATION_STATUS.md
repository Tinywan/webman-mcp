# Implementation Status

## v0.1 Complete

- Composer library boundary, strict public DTOs, Pest, and Mago quality gate
- Pinned MCP `2026-07-28` protocol parsing and deterministic errors
- Immutable multi-Server registry and deny-by-default security policies
- Tool listing/calling, Opis Schema validation, local references, and sanitized failures
- Stateless Streamable HTTP POST transport and Webman request/response adapter
- Publishable plugin config and five Webman Console commands
- Calculator example and end-to-end protocol tests

## Intentional Limits

The release has no Client, Resources, Prompts, Tasks, MRTR, subscriptions, SSE output, legacy
transport, session support, protocol downgrade, OAuth, JWT/JWKS, rate limiting, idempotency, or full
audit implementation. These require separate changes.
