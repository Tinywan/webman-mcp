## ADDED Requirements

### Requirement: Composer package identity and runtime
The package MUST be named `tinywan/webman-mcp`, MUST require PHP `^8.2`, MUST map `Tinywan\Mcp\` to `src/` through PSR-4, and MUST declare `workerman/webman-framework ^2.1` and `opis/json-schema ^2.4` as runtime dependencies. It MUST NOT depend on `mcp/sdk`.

#### Scenario: Composer metadata is validated
- **WHEN** the package metadata and resolved dependency graph are inspected
- **THEN** the required identity, PHP constraint, namespace mapping and runtime dependencies are present and `mcp/sdk` is absent

### Requirement: Stable public contracts
All SDK public types MUST be under `Tinywan\Mcp`, and the SDK MUST expose `ProtocolDriverInterface`, `ToolInterface`, `AuthenticatorInterface`, and `AuthorizerInterface` with domain DTO parameters and results rather than Webman, Opis or third-party protocol types.

#### Scenario: Application implements an extension point
- **WHEN** an application implements a Tool, authenticator or authorizer
- **THEN** its public method signatures depend only on `Tinywan\Mcp` types and PHP built-in types

### Requirement: Read-only execution context
`ExecutionContext` MUST be immutable and MUST carry the authenticated Principal, trace ID, protocol version, client information, client capabilities and deadline for exactly one request.

#### Scenario: Tool receives request context
- **WHEN** the protocol driver invokes a Tool
- **THEN** the Tool receives a read-only context containing all required request metadata and cannot mutate it for another consumer

### Requirement: Strict types for maintained PHP
Every PHP file maintained by this project under source, tests, examples, commands and published PHP configuration MUST place `declare(strict_types=1);` immediately after `<?php`; third-party files under `vendor/` MUST be excluded from this rule.

#### Scenario: Architecture test finds a non-strict file
- **WHEN** a maintained PHP file omits or misplaces the strict types declaration
- **THEN** the project architecture test fails and identifies that file

### Requirement: Unified quality gate
The project MUST use Pest `^3.8` as its test DSL and Mago `^1.45` as its only formatter, linter and static analyzer. `composer check` MUST run format check, lint, analysis and tests in that order, and the versioned Mago configuration MUST NOT use a baseline to suppress new findings.

#### Scenario: Full project verification succeeds
- **WHEN** a maintainer runs `composer check` on a conforming checkout
- **THEN** Mago format check, Mago lint, Mago analysis and Pest tests all run in the required order and exit successfully
