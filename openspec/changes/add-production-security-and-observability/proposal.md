## Why

The SDK has secure deny-by-default extension points, but production adopters still need standard Bearer authentication, bounded execution, abuse controls, audit events, and request telemetry. These facilities must be composable and must not leak credentials or request-scoped state across Webman Workers.

## What Changes

- Add configurable Bearer token authentication with constant-time token verification and explicit Principal construction.
- Add pluggable rate limiting and idempotency contracts with deny-safe defaults and request-scoped decisions.
- Add audit and telemetry contracts covering authentication, authorization, dispatch, handler duration, outcomes, and trace IDs.
- Add request deadline configuration, response-size limits, and concurrency admission controls.
- Provide Webman-friendly logging and metrics adapters without exposing Webman types through public SDK contracts.
- Add security, isolation, redaction, failure-mode, and load-oriented tests plus deployment guidance.

## Capabilities

### New Capabilities

- `bearer-authentication`: Defines secure static Bearer credential verification and Principal creation.
- `request-governance`: Defines rate limiting, idempotency, deadlines, concurrency, and response-size enforcement.
- `audit-observability`: Defines redacted audit events, metrics, tracing, and failure isolation.

### Modified Capabilities

None.

## Impact

This extends public security and observability contracts, Server options, transport middleware, runtime context, Webman adapters, configuration, tests, and deployment documentation. Existing explicit authenticators and authorizers remain supported.
