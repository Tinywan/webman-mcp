## Why

The SDK claims MCP `2026-07-28` compatibility, but its checked-in schema asset is only a hand-written
summary of upstream invariants rather than the pinned official schema it references. The repository
needs a reproducible, offline conformance boundary so protocol behavior and Codex interoperability can
be verified against the authoritative release rather than inferred from local metadata.

## What Changes

- Replace the summary-only baseline asset with the complete official MCP `2026-07-28` schema pinned to
  commit `271ecc9accafdd9b83a3c869fa67c22953b2af80` and verify its recorded SHA-256 digest.
- Add focused conformance tests for the supported `server/discover`, `tools/list`, and `tools/call`
  request and result shapes, including required request metadata, routing Headers, cache hints, and
  complete Tool results.
- Add a Codex-compatible Streamable HTTP request fixture that exercises the same wire contract without
  introducing Client behavior into the SDK.
- Correct compatibility documentation so `Mcp-Method` and conditional `Mcp-Name` are described as
  official `2026-07-28` routing Headers, while handshake and session fields remain explicitly absent.
- Keep MRTR, Tasks, Resources, Prompts, subscriptions, protocol downgrade, and legacy transports out of
  scope.

## Capabilities

### New Capabilities

- `official-schema-conformance`: Offline pinning and verification of the complete official MCP
  `2026-07-28` schema plus conformance fixtures for the SDK's supported Server methods.

### Modified Capabilities

None.

## Impact

- Affects `resources/schema`, protocol/transport tests, compatibility documentation, and Calculator
  integration fixtures.
- Does not change the public `Tinywan\Mcp` extension contracts or add runtime network access.
- Increases the repository size by the upstream schema fixture and keeps Opis limited to Tool input and
  output validation at runtime.
