## Context

The runtime implements a deliberately narrow MCP `2026-07-28` Server surface: stateless POST,
`server/discover`, `tools/list`, and `tools/call`. Its current `resources/schema` asset records selected
facts and the digest of the upstream schema, but does not contain the official schema itself. This
makes the pin impossible to audit offline and lets local request/result assumptions drift without a
machine-readable authoritative fixture.

## Goals / Non-Goals

**Goals:**

- Check in the exact complete schema from the already recorded upstream commit and verify its digest.
- Exercise supported wire messages against named definitions in that schema without adding runtime
  network access.
- Preserve official Header routing, stateless metadata, cache hints, and complete Tool result behavior.
- Represent a Codex-originated request as a test fixture while keeping this package Server-only.

**Non-Goals:**

- Adding a Client, protocol negotiation, downgrade, `initialize`, sessions, or legacy transport.
- Implementing MRTR, Tasks, Resources, Prompts, subscriptions, or SSE output.
- Expanding production authentication beyond the existing extension contracts.
- Exposing Opis types through public SDK contracts or validating all protocol messages at runtime.

## Decisions

### Store the byte-exact upstream schema

Add `resources/schema/2026-07-28-schema.json` from the pinned commit and remove the misleading
summary-only baseline file. Record the source, commit, byte count, and SHA-256 in the schema README and
assert them in tests. A complete fixture is preferred over a generated subset because it remains
independently auditable and satisfies the repository protocol-boundary rule.

### Validate only the supported protocol surface

Tests will resolve and validate the official schema definitions that correspond to the three supported
methods and their results. The SDK will not claim support for every definition present in the full
schema. Runtime parsing stays explicit and typed; the official schema is a development conformance
fixture, not a request-time dependency.

### Model Codex as wire input, not an SDK Client

A JSON fixture will contain the required `_meta` protocol version, client capabilities, and Codex
client identity. The existing HTTP integration path will attach official `MCP-Protocol-Version`,
`Mcp-Method`, and conditional `Mcp-Name` Headers. This verifies interoperability at the Server boundary
without adding connection management or Client APIs.

### Preserve the current limited capability surface

`ttlMs: 0` and `cacheScope: private` remain valid conservative cache hints. Tool calls continue to
return `resultType: complete`; MRTR result variants remain deferred. Unsupported methods continue to
return method-not-found.

## Risks / Trade-offs

- **Upstream definition names may differ from local assumptions** → inspect the pinned schema and bind
  tests to its actual named definitions rather than inventing aliases.
- **The complete fixture increases repository size** → accept the roughly 181 KB cost for offline,
  reproducible protocol verification.
- **Codex may not yet speak `2026-07-28` in every release channel** → test the Server wire contract and
  document that the installed Codex version must support the pinned protocol.
- **Schema validation can become coupled to Opis behavior** → keep validation test-only and retain
  explicit runtime parsing and sanitized errors.
