## ADDED Requirements

### Requirement: Complete official schema fixture
The repository SHALL contain the byte-exact official MCP `2026-07-28` schema from commit
`271ecc9accafdd9b83a3c869fa67c22953b2af80` and SHALL verify its recorded SHA-256 digest offline.

#### Scenario: Maintainer verifies the pinned schema
- **WHEN** the protocol conformance tests run without network access
- **THEN** the checked-in schema matches the expected byte count, SHA-256 digest, and protocol release

### Requirement: Supported requests conform to the official schema
Representative `server/discover`, `tools/list`, and `tools/call` requests SHALL validate against the
corresponding official MCP `2026-07-28` request definitions.

#### Scenario: Codex-shaped Tool call is validated
- **WHEN** a stateless Tool call contains the required protocol metadata, client capabilities, optional
  Codex client information, Tool name, and arguments
- **THEN** the message validates against the official Tool call request definition

### Requirement: Supported results conform to the official schema
Representative discovery, Tool list, and complete Tool call results SHALL validate against the
corresponding official MCP `2026-07-28` result definitions.

#### Scenario: Calculator returns a complete result
- **WHEN** the Calculator Tool successfully adds two numbers
- **THEN** its JSON-RPC result, content, structured content, result type, and list cache hints validate
  against the official supported result definitions

### Requirement: Official stateless routing contract
The Server SHALL require the official `MCP-Protocol-Version` and `Mcp-Method` routing Headers and SHALL
require `Mcp-Name` for `tools/call`, with each Header value matching the JSON-RPC body.

#### Scenario: Official routing Headers match the body
- **WHEN** a Codex-compatible request supplies matching protocol version, method, and Tool name Headers
- **THEN** the Server processes the request without a legacy handshake or protocol session

#### Scenario: Routing Header conflicts with the body
- **WHEN** a routing Header does not match its JSON-RPC body field
- **THEN** the Server returns the official Header mismatch protocol error

### Requirement: Scope remains Server-only
The change SHALL NOT introduce Client behavior, legacy protocol fallbacks, session state, MRTR, Tasks,
Resources, Prompts, subscriptions, or SSE output.

#### Scenario: Unsupported capability is requested
- **WHEN** a request uses a method outside `server/discover`, `tools/list`, or `tools/call`
- **THEN** the Server returns method-not-found and does not negotiate or downgrade the protocol
