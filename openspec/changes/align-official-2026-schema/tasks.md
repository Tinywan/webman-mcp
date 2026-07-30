## 1. Official Schema Fixture

- [x] 1.1 Download the complete official MCP `2026-07-28` schema from the pinned commit
- [x] 1.2 Replace the summary baseline and document the source, size, and SHA-256 digest
- [x] 1.3 Add an offline integrity test for the pinned schema asset

## 2. Protocol Conformance

- [x] 2.1 Identify the official schema definitions for the three supported requests and results
- [x] 2.2 Add valid official-schema fixtures for discovery, Tool listing, and Tool calling
- [x] 2.3 Add a Codex-shaped stateless Tool call integration fixture with official routing Headers
- [x] 2.4 Verify unsupported methods remain method-not-found without negotiation or session fallback

## 3. Documentation and Quality

- [x] 3.1 Update compatibility and README language to match the official `2026-07-28` release
- [x] 3.2 Run Mago formatting, linting, analysis, Pest, and `composer check`
- [x] 3.3 Confirm the OpenSpec tasks and implementation status are complete
