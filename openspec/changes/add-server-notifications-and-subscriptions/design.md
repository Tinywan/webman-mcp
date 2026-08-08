## Context

The current result family reserves `StreamResult` but production transport never emits it. The official protocol now defines `subscriptions/listen` and typed notifications. Supporting these requires a bounded outbound stream without legacy session creation or mutable registry state.

## Goals / Non-Goals

**Goals:**

- Implement authorized subscription listening and typed Server notifications.
- Add request-scoped progress and cancellation primitives.
- Bound stream memory, lifetime, and failure behavior.

**Non-Goals:**

- Legacy GET/DELETE SSE transport, resumable `Mcp-Session-Id`, or protocol downgrade.
- Client request methods such as sampling, roots, or elicitation.
- Guaranteed delivery across process failure.

## Decisions

- `subscriptions/listen` remains a POST request. A successful request maps to `StreamResult` with `text/event-stream`; no session identifier is created or echoed.
- A per-request `SubscriptionProviderInterface` returns an iterable of typed outbound messages and is never cached by the Worker registry.
- Encode every event as an official JSON-RPC notification envelope. Emit an acknowledgement first, then bounded events, then close at deadline or provider completion.
- Add `CancellationTokenInterface` and `ProgressReporterInterface` to `ExecutionContext` with no-op defaults. Incoming cancellation notifications address active work through an injected process-local coordinator, never global mutable SDK state.
- Apply per-event and aggregate byte limits. Slow or disconnected consumers cancel provider iteration and emit audit outcomes.

## Risks / Trade-offs

- [Long POST responses consume Worker capacity] -> Add concurrency admission, deadlines, and explicit limits.
- [No cross-Worker cancellation] -> Keep the coordinator pluggable so applications can use Redis or another shared backend.
- [No replay after disconnect] -> Document at-most-once delivery; durable replay remains out of scope.

## Migration Plan

Streaming remains disabled unless a Server registers a subscription provider. Existing JSON and 202 paths remain unchanged.

## Open Questions

None.
