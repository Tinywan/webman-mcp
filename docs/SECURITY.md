# Security

## Defaults

`ServerDefinition` installs `DenyAllAuthenticator` and `DenyAllAuthorizer` unless the application
supplies explicit policies. `AllowAnonymousAuthenticator` is intended for controlled examples and
public endpoints only; the Calculator uses it for local demonstration.

`canList` and `canCall` are independent checks. Knowing a hidden Tool name is never authorization to
invoke it.

## Transport Controls

- POST-only endpoint with strict JSON and dual-`Accept` negotiation
- Default 2 MiB request body limit, configurable per Server
- Per-Server Origin allowlist evaluated before body parsing and dispatch
- Case-insensitive HTTP Header handling with strict Header/body mirrors
- Strict Base64 sentinel decoding for Header values
- No PHP session, `Mcp-Session-Id`, `Last-Event-ID`, or SSE response state

## Tool Controls

Tool input and optional output use JSON Schema 2020-12. Registration accepts only fragment-local
`$ref`/`$defs`; HTTP, file, and other external references are rejected before Opis validation, so
Schema validation cannot perform network or filesystem resolution.

Unhandled Tool exceptions and output contract violations become sanitized internal errors. Client
responses contain a trace ID but not stack traces, credentials, Principal attributes, or raw exception
messages. Deadlines are cooperative in v0.1 and cannot forcibly interrupt arbitrary Tool code.
