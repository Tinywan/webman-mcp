## ADDED Requirements

### Requirement: Secure Bearer extraction
The built-in authenticator SHALL accept exactly one syntactically valid Bearer credential and SHALL reject missing, duplicated, malformed, or unsupported authorization schemes.

#### Scenario: Valid Bearer credential
- **WHEN** a request supplies one configured Bearer credential
- **THEN** authentication SHALL produce the configured Principal without storing the credential

### Requirement: Constant-time static verification
Static Bearer verification MUST compare credential digests in constant time and MUST not retain plaintext configured tokens after construction.

#### Scenario: Invalid token
- **WHEN** a supplied token does not match any configured digest
- **THEN** authentication SHALL fail without revealing whether an identifier or digest matched

### Requirement: Pluggable verifier boundary
JWT, JWKS, and remote verification adapters SHALL implement an SDK-owned contract and SHALL receive no Webman types.

#### Scenario: Verifier failure
- **WHEN** a verifier is unavailable or throws
- **THEN** authentication SHALL fail closed and the client SHALL receive a sanitized unauthorized response
