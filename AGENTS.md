# Repository Guide

## Scope

- Maintain MCP Server behavior only. Do not add Client behavior or legacy protocol fallbacks.
- Protocol changes require a separate OpenSpec change and an updated pinned official schema fixture.
- Public SDK types belong under `Tinywan\Mcp`; do not expose Webman or Opis types through extension contracts.

## Quality

- Every maintained PHP file under `src`, `tests`, `examples`, and `config` must declare strict types.
- Use Pest for tests and Mago for formatting, linting, and analysis. Do not introduce a baseline.
- Run `composer check` before completion.
- Keep Worker-cached state immutable. Resolve Tool handlers for each call and pass request state explicitly.

## Protocol Boundaries

- Supported version: `2026-07-28`.
- Supported methods: `server/discover`, `tools/list`, `tools/call`.
- Transport: stateless POST; no SSE output, session state, subscriptions, or downgrade path.
- Default authentication and authorization deny access. Anonymous access must be explicit.
