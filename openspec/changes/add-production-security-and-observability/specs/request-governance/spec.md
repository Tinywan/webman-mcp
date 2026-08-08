## ADDED Requirements

### Requirement: Admission controls
The transport SHALL apply rate, concurrency, and body admission controls before parsing or handler resolution.

#### Scenario: Rate limit exceeded
- **WHEN** the configured limiter rejects a request
- **THEN** the request SHALL not dispatch and SHALL return a bounded non-sensitive error

### Requirement: Deadline and output bounds
Each request MUST have a configured maximum duration and serialized response-size limit enforced across JSON and stream results.

#### Scenario: Oversized response
- **WHEN** a valid handler result exceeds the response limit
- **THEN** the transport SHALL replace it with a sanitized internal error and a trace ID

### Requirement: Explicit idempotency
Idempotency SHALL apply only to configured request methods and valid client keys through an application-provided store.

#### Scenario: Replayed idempotent call
- **WHEN** an identical authorized call repeats with a valid completed idempotency key
- **THEN** the stored outcome SHALL be returned without resolving the handler again

### Requirement: Request-state isolation
Governance decisions and leases MUST be request-scoped and released after every success, failure, cancellation, or disconnect.

#### Scenario: Interleaved requests
- **WHEN** multiple Principals share a long-lived Worker
- **THEN** their limiter keys, deadlines, idempotency records, and concurrency leases SHALL not leak across requests
