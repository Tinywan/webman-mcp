## ADDED Requirements

### Requirement: Immutable Resource registration
Each Server SHALL register immutable exact Resource and Resource Template definitions with SDK-owned public types and application handler identifiers.

#### Scenario: Valid Resource topology
- **WHEN** a Server registers unique Resource URIs and Resource Template patterns
- **THEN** the Worker registry SHALL cache only validated definitions and handler identifiers

### Requirement: Resource uniqueness validation
The registry MUST reject duplicate exact URIs, duplicate template patterns, malformed URIs, and malformed templates before traffic is served.

#### Scenario: Duplicate Resource URI
- **WHEN** two Resources in one Server use the same normalized URI
- **THEN** Server registry construction SHALL fail with a deterministic diagnostic

### Requirement: Per-read handler resolution
The SDK SHALL resolve a fresh Resource handler for every read request and SHALL pass request state explicitly.

#### Scenario: Interleaved Principals
- **WHEN** two Principals read the same Resource in one Worker
- **THEN** their handler instances and request contexts SHALL not be shared
