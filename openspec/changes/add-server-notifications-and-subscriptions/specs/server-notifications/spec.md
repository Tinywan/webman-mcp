## ADDED Requirements

### Requirement: Typed official notifications
The SDK SHALL model list changes, Resource updates, progress, messages, cancellation, and subscription acknowledgements as SDK-owned official notification DTOs.

#### Scenario: Notification serialization
- **WHEN** a valid notification DTO is serialized
- **THEN** it SHALL produce one pinned-schema-conformant JSON-RPC notification without an ID

### Requirement: Request-scoped progress
Handlers SHALL report bounded progress through the Execution Context without depending on Webman or transport types.

#### Scenario: Progress after cancellation
- **WHEN** a handler reports progress after its cancellation token is cancelled
- **THEN** the reporter SHALL reject or ignore the update without emitting another event

### Requirement: Sanitized server messages
Server message notifications MUST exclude credentials, Principal attributes, stack traces, and raw exception messages.

#### Scenario: Handler failure message
- **WHEN** a handler failure is reported to a subscriber
- **THEN** the notification SHALL contain a trace ID and categorical outcome only
