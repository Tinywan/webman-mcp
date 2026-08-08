## ADDED Requirements

### Requirement: Official Resource methods
The native driver SHALL route `resources/list`, `resources/read`, and `resources/templates/list` using the pinned official protocol schemas.

#### Scenario: Resource read request
- **WHEN** a valid authorized `resources/read` request is dispatched
- **THEN** the driver SHALL return an official complete Resource read result preserving the request ID

### Requirement: Resource discovery advertisement
`server/discover` SHALL advertise Resource capability only when at least one Resource or Resource Template is visible to the current Principal.

#### Scenario: No visible Resources
- **WHEN** the current Principal cannot list any Resource or Template
- **THEN** discovery SHALL omit Resource capability details

### Requirement: Resource protocol conformance
Every Resource request and result fixture MUST validate offline against the pinned official Schema.

#### Scenario: Conformance suite
- **WHEN** Resource protocol fixtures are tested
- **THEN** request and response documents SHALL validate without network access
