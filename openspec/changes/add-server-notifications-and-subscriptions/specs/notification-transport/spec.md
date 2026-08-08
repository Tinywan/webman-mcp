## ADDED Requirements

### Requirement: POST event-stream response
A successful `subscriptions/listen` POST SHALL return an official event stream without creating or echoing session state.

#### Scenario: Subscription response
- **WHEN** a valid subscription listen request is accepted
- **THEN** the transport SHALL return `text/event-stream` events and SHALL omit `Mcp-Session-Id`

### Requirement: Existing stateless paths remain stable
Non-subscription requests and notifications SHALL retain their JSON response and empty 202 behavior.

#### Scenario: Tool call after streaming enabled
- **WHEN** a normal Tool call is dispatched on a Server that also supports subscriptions
- **THEN** it SHALL still return one JSON envelope and no SSE body

### Requirement: No legacy transport fallback
The transport MUST continue to reject legacy GET/DELETE event transport and protocol downgrade behavior.

#### Scenario: Legacy GET attempt
- **WHEN** a client sends GET with an event-stream Accept header
- **THEN** the transport SHALL return 405 with `Allow: POST`
