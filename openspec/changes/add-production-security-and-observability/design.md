## Context

Authentication and authorization are deny-by-default extension points, but the request pipeline has no built-in credential verifier, admission policies, audit sink, or metrics recorder. These controls must work in long-lived Workers without caching request identity or secrets.

## Goals / Non-Goals

**Goals:**

- Provide safe production defaults and composable governance contracts.
- Record redacted, structured lifecycle events without affecting protocol outcomes.
- Bound request concurrency, duration, and output.

**Non-Goals:**

- Running an OAuth authorization server or persisting user accounts.
- Mandating a metrics vendor, logger, cache, or distributed lock service.
- Exposing Webman request/response objects through public contracts.

## Decisions

- Provide `StaticBearerAuthenticator` for configured hashed tokens and a `BearerTokenVerifierInterface` for JWT/JWKS or remote verification adapters. Never store plaintext token values in Principal attributes or diagnostics.
- Define independent `RateLimiterInterface`, `IdempotencyStoreInterface`, and `ConcurrencyLimiterInterface` decisions using SDK request descriptors. Defaults deny unsafe protected behavior or explicitly disable optional governance when not configured.
- Add immutable governance options to `ServerOptions`: deadline, response bytes, concurrency, idempotency TTL, and audit policy.
- Define `AuditSinkInterface` and `TelemetryInterface` with null implementations. Sink failures are swallowed after safe fallback logging and cannot change protocol responses.
- Emit events at authentication, authorization, dispatch, handler, response, and stream termination boundaries using trace IDs and categorical outcomes only.

## Risks / Trade-offs

- [Static tokens are operationally limited] -> Position them for controlled deployments and keep verifier adapters pluggable.
- [Failing observability hides incidents] -> Count sink failures through fallback telemetry without leaking request data.
- [Idempotency can cache sensitive results] -> Store only application-selected methods, encrypted application-owned payloads, or response fingerprints by policy.

## Migration Plan

All governance components are opt-in except existing deny-all authentication and authorization. Applications can enable controls per Server without changing handler contracts.

## Open Questions

None.
