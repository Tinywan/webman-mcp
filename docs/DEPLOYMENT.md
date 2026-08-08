# Production Deployment

## Credentials

Store only SHA-256 Bearer token digests in application configuration or a secret-backed adapter.
Map successful verification to a minimal `Principal`; do not place tokens, authorization Headers, or
unneeded identity claims in Principal attributes. Use a custom `BearerTokenVerifierInterface` for
JWT/JWKS or remote verification and fail closed when its dependency is unavailable.

## Governance

Configure a finite deadline and response byte limit for every Server. Add a distributed rate limiter
and concurrency limiter when multiple Workers or hosts share traffic. Process-local implementations
are suitable only for single-Worker limits. Enable idempotency only for selected methods and use an
application-owned store with encryption and bounded TTL when response payloads are sensitive.

## Observability

Connect `AuditSinkInterface` and `TelemetryInterface` adapters to production logging and metrics.
Audit records contain only categorical stages, Server ID, trace ID, method, outcome, and timing. Alert
on `mcp.observability.failure`, rate rejection, concurrency rejection, internal errors, and stream
termination. Sink failures never change protocol responses.

## Edge Proxy

Terminate TLS at the application or trusted proxy, restrict allowed Origins, retain POST request body
limits, and disable proxy buffering for `text/event-stream`. Do not add session cookies, SSE replay
IDs, protocol downgrade, or legacy GET/DELETE event routes.
