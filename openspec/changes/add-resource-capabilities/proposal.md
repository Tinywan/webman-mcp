## Why

The SDK currently exposes Tools only, so MCP clients cannot discover or read application-managed context through the standard Resource capability. Resource support is the next independent server-side protocol surface needed for a broadly useful MCP Server SDK.

## What Changes

- Add immutable Resource and Resource Template definitions under the public `Tinywan\Mcp` SDK namespace.
- Add per-request Resource handlers and resolver contracts without caching request-scoped handler state.
- Add Principal-aware listing and reading with cursor pagination and non-disclosing authorization failures.
- Route official `resources/list`, `resources/read`, and `resources/templates/list` requests.
- Advertise Resource capabilities through `server/discover` only when visible Resources or Templates exist.
- Add official Schema conformance, runtime, isolation, Webman integration, examples, and documentation coverage.

## Capabilities

### New Capabilities

- `resource-registry`: Defines immutable Resource and Resource Template registration, uniqueness, and resolution.
- `resource-runtime`: Defines authorized Resource listing, template listing, pagination, and content reads.
- `resource-protocol`: Defines the three supported Resource methods and discovery capability advertisement.

### Modified Capabilities

None.

## Impact

This extends public SDK contracts and DTOs, `ServerDefinition`, registry validation, the native protocol driver, resolver assembly, examples, tests, and protocol documentation. It is additive for applications that configure no Resources.
