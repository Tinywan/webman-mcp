## ADDED Requirements

### Requirement: Official Prompt and Completion methods
The driver SHALL route `prompts/list`, `prompts/get`, and `completion/complete` using pinned official schemas.

#### Scenario: Valid completion request
- **WHEN** a valid authorized completion request is dispatched
- **THEN** the driver SHALL return an official complete result preserving the request ID

### Requirement: Principal-aware capability advertisement
Discovery SHALL advertise Prompt and Completion capabilities only when the current Principal has access to corresponding providers.

#### Scenario: Tool-only Principal
- **WHEN** a Principal can use Tools but no Prompts or Completion targets
- **THEN** discovery SHALL not advertise Prompt or Completion capabilities

### Requirement: Prompt and Completion conformance
Prompt and Completion fixtures MUST validate offline against the pinned official Schema.

#### Scenario: Conformance suite
- **WHEN** Prompt and Completion protocol fixtures are tested
- **THEN** all supported request and result documents SHALL validate without network access
