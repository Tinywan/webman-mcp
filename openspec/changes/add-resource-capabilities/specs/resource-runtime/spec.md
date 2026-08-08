## ADDED Requirements

### Requirement: Authorized Resource discovery
The runtime SHALL independently filter exact Resources and Resource Templates for the current Principal without exposing hidden metadata.

#### Scenario: Hidden Resource
- **WHEN** a Principal lacks list permission for a Resource
- **THEN** its URI, name, description, media type, annotations, and handler identity SHALL be absent from the response

### Requirement: Cursor pagination
Resource and Resource Template listing SHALL use deterministic opaque cursors bound to the immutable Server definition.

#### Scenario: Invalid cursor
- **WHEN** a cursor is malformed or belongs to a different definition set
- **THEN** the runtime SHALL return invalid params without returning a partial page

### Requirement: Authorized Resource read
The runtime SHALL resolve and read only exact or template-matched Resources authorized for the current Principal and SHALL return official Resource content DTOs.

#### Scenario: Unknown or unauthorized URI
- **WHEN** a URI is unknown or the Principal lacks read permission
- **THEN** the runtime SHALL return one indistinguishable invalid-params error and SHALL not resolve a handler

### Requirement: Bounded Resource output
Resource reads MUST enforce declared content types and configured aggregate response limits before serialization.

#### Scenario: Oversized content
- **WHEN** a Resource handler returns content above the configured limit
- **THEN** the runtime SHALL return a sanitized internal error with a trace ID
