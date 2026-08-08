# Repository Guide

## Scope

- Maintain MCP Server behavior only. Do not add Client behavior or legacy protocol fallbacks.
- Protocol changes require a separate OpenSpec change and an updated pinned official schema fixture.
- Public SDK types belong under `Tinywan\Mcp`; do not expose Webman or Opis types through extension contracts.

## Quality

- Every maintained PHP file under `src`, `tests`, `examples`, and `config` must declare strict types.
- Use Pest for tests and Mago for formatting, linting, and analysis. Do not introduce a baseline.
- Run `composer check` before completion.
- Keep Worker-cached state immutable. Resolve Tool, Resource, Prompt, Completion, and Subscription providers for each call and pass request state explicitly.

## Protocol Boundaries

- Supported version: `2026-07-28`.
- Supported methods: `server/discover`, `tools/list`, `tools/call`, `resources/list`, `resources/read`, `resources/templates/list`, `prompts/list`, `prompts/get`, `completion/complete`, `subscriptions/listen`, `notifications/cancelled`.
- Transport: stateless POST; bounded SSE output only for `subscriptions/listen`; no session state, replay, GET/DELETE event transport, or downgrade path.
- Default authentication and authorization deny access. Anonymous access must be explicit.
