## 1. Authentication

- [x] 1.1 Add strict Bearer token parser and verifier contracts without Webman types
- [x] 1.2 Implement digest-only constant-time static Bearer authentication and Principal mapping
- [x] 1.3 Add malformed, duplicate, invalid, verifier failure, secret lifetime, and redaction tests

## 2. Request Governance

- [x] 2.1 Add SDK-owned rate, concurrency, idempotency, deadline, and response-limit contracts and decisions
- [x] 2.2 Extend immutable Server options and configuration with bounded governance settings
- [x] 2.3 Apply admission controls before parsing and release every lease on success, failure, cancellation, and disconnect
- [x] 2.4 Implement explicit method-scoped idempotency replay without handler re-execution
- [x] 2.5 Enforce duration and serialized output limits for JSON and stream responses
- [x] 2.6 Add interleaved Principal, limit, replay, failure, and cleanup tests

## 3. Audit and Telemetry

- [x] 3.1 Add redacted audit event and telemetry DTOs plus sink contracts and null implementations
- [x] 3.2 Instrument authentication, authorization, dispatch, handler, response, cancellation, and stream boundaries
- [x] 3.3 Add Webman-friendly logging and metrics adapters behind SDK contracts
- [x] 3.4 Add correlation, timing, redaction, sink-failure isolation, and Worker-state tests

## 4. Configuration and Acceptance

- [x] 4.1 Publish safe configuration examples and extend `mcp:inspect` diagnostics without exposing secrets
- [x] 4.2 Update README, architecture, security, compatibility, implementation status, and deployment guidance
- [x] 4.3 Run focused tests, the complete security matrix, and `composer check`
