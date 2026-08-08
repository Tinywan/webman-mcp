## ADDED Requirements

### Requirement: Authorized subscription listening
The Server SHALL authorize each `subscriptions/listen` request before resolving a fresh subscription provider.

#### Scenario: Unauthorized subscription
- **WHEN** a Principal requests a topic without listen permission
- **THEN** the runtime SHALL return invalid params and SHALL not resolve the provider

### Requirement: Bounded at-most-once delivery
Subscriptions SHALL deliver an acknowledgement followed by ordered events with bounded count, bytes, and lifetime.

#### Scenario: Provider exceeds limits
- **WHEN** a provider produces events beyond a configured bound
- **THEN** iteration SHALL be cancelled and the stream SHALL terminate safely

### Requirement: Disconnect cleanup
The runtime MUST cancel provider iteration and release request-scoped resources when the consumer disconnects.

#### Scenario: Client disconnect
- **WHEN** the HTTP connection closes during a subscription
- **THEN** the cancellation token SHALL be signalled and no further event SHALL be encoded
