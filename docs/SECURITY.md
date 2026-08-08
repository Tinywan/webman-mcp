# Security

## Defaults

`ServerDefinition` installs deny-all authentication and per-capability authorization unless the application
supplies explicit policies. `AllowAnonymousAuthenticator` is intended for controlled examples and
public endpoints only; the Calculator uses it for local demonstration.

`canList` and `canCall` are independent checks. Knowing a hidden Tool name is never authorization to
invoke it.

`StaticBearerAuthenticator` accepts exactly one syntactically valid Bearer credential. Its static
verifier stores only SHA-256 digests, compares them with `hash_equals`, and never copies credentials
into a Principal or diagnostic. Custom verifier failures are sanitized and fail closed.

Resource list, template list, exact read, and template read checks are independent. Unknown and
unauthorized Resource URIs return the same error and do not resolve a handler.

## Transport Controls

- POST-only endpoint with strict JSON and dual-`Accept` negotiation
- Default 2 MiB request body limit, configurable per Server
- Per-Server Origin allowlist evaluated before body parsing and dispatch
- Case-insensitive HTTP Header handling with strict Header/body mirrors
- Strict Base64 sentinel decoding for Header values
- No PHP session, `Mcp-Session-Id`, `Last-Event-ID`, replay state, or legacy GET/DELETE event transport
- Principal-scoped rate and concurrency admission before parsing and handler resolution
- Finite request deadline and serialized JSON/SSE response byte limits
- Method-scoped, fingerprint-bound idempotency only when an application store is configured

## Tool Controls

Tool input and optional output use JSON Schema 2020-12. Registration accepts only fragment-local
`$ref`/`$defs`; HTTP, file, and other external references are rejected before Opis validation, so
Schema validation cannot perform network or filesystem resolution.

Unhandled Tool exceptions and output contract violations become sanitized internal errors. Client
responses contain a trace ID but not stack traces, credentials, Principal attributes, or raw exception
messages. Deadlines are cooperative in v0.1 and cannot forcibly interrupt arbitrary Tool code.

## Resource Controls

Resource definitions and URI Templates are validated before traffic. List results are filtered before
pagination, cursors are bound to the visible immutable definition set, and handlers are resolved for
each read. Resource responses have a configurable aggregate byte limit; handler exceptions and limit
violations return sanitized internal errors with a trace ID.

## Prompt and Completion Controls

Prompt list, get, and Completion authorization are independent. Prompt arguments must be declared
strings and all required arguments must be present before a renderer is resolved. Completion targets
must be registered Prompt or Resource references; results are deduplicated and capped at 100 values.
Renderer/provider failures and expired deadlines return sanitized errors.

## Notification and Subscription Controls

Subscription authorization runs before provider resolution, and providers are resolved for each
request. Clients receive only explicitly requested and server-supported notification types. Streams
are bounded by event count, per-event bytes, aggregate bytes, and lifetime; cancellation stops further
encoding. Delivery is ordered and at most once, with no replay after disconnect. Server message DTOs
expose only a trace ID and categorical outcome, never credentials, Principal attributes, stack traces,
or raw exception messages.

## Audit and Telemetry Controls

Audit DTOs are redacted by construction: they contain Server ID, trace ID, categorical stage/outcome,
safe method name, and duration only. They cannot carry authorization Headers, tokens, arguments,
Principal attributes, stack traces, or exception messages. Audit and telemetry sink failures are
isolated from protocol outcomes and attempt a safe `mcp.observability.failure` counter.
