## ADDED Requirements

### Requirement: Immutable Prompt registration
Each Server SHALL register unique immutable Prompt definitions and resolve a fresh renderer for each get request.

#### Scenario: Duplicate Prompt name
- **WHEN** two Prompts in one Server use the same name
- **THEN** registry construction SHALL fail before traffic is served

### Requirement: Authorized Prompt discovery and rendering
The runtime SHALL apply independent list and get authorization and SHALL not expose hidden Prompt metadata or messages.

#### Scenario: Known unauthorized Prompt
- **WHEN** a Principal requests a known Prompt without get permission
- **THEN** the runtime SHALL return the same invalid-params response used for an unknown Prompt

### Requirement: Prompt argument validation
The runtime MUST validate required, declared, and unknown arguments before resolving a Prompt renderer.

#### Scenario: Missing required argument
- **WHEN** a Prompt request omits a required argument
- **THEN** the runtime SHALL return invalid params without invoking the renderer

### Requirement: Official Prompt messages
Prompt renderers SHALL return SDK-owned message and content DTOs that serialize to the pinned official result schema.

#### Scenario: Valid rendered Prompt
- **WHEN** an authorized renderer returns valid messages
- **THEN** the result SHALL preserve role, content, description, and request ID
