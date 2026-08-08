## ADDED Requirements

### Requirement: Authorized typed completion
Completion SHALL accept only registered Prompt or Resource Template references authorized for the current Principal.

#### Scenario: Hidden completion target
- **WHEN** a Principal requests completion for a hidden or unknown reference
- **THEN** the runtime SHALL return indistinguishable invalid params without invoking a provider

### Requirement: Bounded completion values
Completion providers MUST return unique string values bounded by the configured item count and response-size limits.

#### Scenario: Provider returns excess values
- **WHEN** a provider returns more values than the configured maximum
- **THEN** the runtime SHALL truncate deterministically and report whether more values exist

### Requirement: Completion deadline and failure handling
The runtime SHALL enforce the request deadline and sanitize provider failures.

#### Scenario: Completion provider throws
- **WHEN** a provider throws an exception
- **THEN** the runtime SHALL return a sanitized internal error containing only the trace ID
