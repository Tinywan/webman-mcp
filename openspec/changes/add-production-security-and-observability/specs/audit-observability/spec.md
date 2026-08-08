## ADDED Requirements

### Requirement: Structured lifecycle audit
The SDK SHALL emit structured audit events for authentication, authorization, dispatch, handler execution, response, cancellation, and stream termination.

#### Scenario: Successful Tool call
- **WHEN** an authorized Tool call completes
- **THEN** audit events SHALL correlate the categorical stages using one trace ID without recording arguments or credentials by default

### Requirement: Redaction by construction
Audit and telemetry DTOs MUST exclude authorization headers, plaintext tokens, Principal attributes, raw arguments, stack traces, and raw exception messages.

#### Scenario: Authentication failure
- **WHEN** a Bearer credential is rejected
- **THEN** observability output SHALL record only Server identity, trace ID, outcome category, and safe timing fields

### Requirement: Sink failure isolation
Audit and telemetry sink failures MUST NOT alter protocol responses or expose sink exceptions.

#### Scenario: Audit sink throws
- **WHEN** an audit sink throws during response recording
- **THEN** the original protocol outcome SHALL be preserved and a safe fallback failure counter SHALL be attempted

### Requirement: Vendor-neutral public contracts
Public observability contracts SHALL use only SDK-owned DTOs and PHP built-in types.

#### Scenario: Webman adapter
- **WHEN** Webman logging or metrics is connected
- **THEN** Webman types SHALL remain inside adapter implementation boundaries
