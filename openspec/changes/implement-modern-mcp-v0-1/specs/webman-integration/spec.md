## ADDED Requirements

### Requirement: Publishable Webman configuration
The package MUST provide publishable `app.php`, `servers.php` and route configuration whose defaults are valid, deny anonymous access and can declare multiple independent Server endpoints.

#### Scenario: Package is installed into a Webman application
- **WHEN** the application publishes the package configuration
- **THEN** it receives the application, Server and route configuration needed to register MCP endpoints without enabling anonymous access by default

### Requirement: Installation and diagnostics commands
The package MUST provide `mcp:install`, `mcp:list` and `mcp:inspect`. Installation MUST publish required assets idempotently without overwriting user changes; listing MUST show resolved Server/Tool topology; inspection MUST validate configuration, uniqueness, Tool definitions and Schemas with actionable failures.

#### Scenario: Invalid multi-Server configuration is inspected
- **WHEN** a maintainer runs `mcp:inspect` with duplicate paths or an invalid Tool Schema
- **THEN** the command exits unsuccessfully and identifies each invalid configuration location without starting a Worker

#### Scenario: Existing published configuration is installed again
- **WHEN** `mcp:install` encounters a target file already owned or modified by the application
- **THEN** it preserves that file and reports the conflict instead of overwriting it

### Requirement: Safe code generators
The package MUST provide `make:mcp-server` and `make:mcp-tool`. Generated PHP MUST use the `Tinywan\Mcp` contracts, MUST include `declare(strict_types=1);`, MUST pass Mago format check, lint and analysis, and MUST refuse to overwrite an existing target.

#### Scenario: Tool class is generated
- **WHEN** a developer generates a Tool into a free target path
- **THEN** the command creates a strict, syntactically valid implementation scaffold that passes configured Mago checks

#### Scenario: Generator target exists
- **WHEN** a generator resolves to an existing file
- **THEN** the command exits unsuccessfully and leaves the existing bytes unchanged

### Requirement: Runnable Calculator example
The repository MUST include a Calculator Server example demonstrating explicit anonymous authentication, discovery, Tool listing and at least one schema-validated Tool call without implying that anonymous authentication is the production default.

#### Scenario: Example request flow is executed
- **WHEN** a developer follows the Calculator quick start
- **THEN** the developer can discover the example Server, list the Calculator Tool and receive a complete result from a valid call

### Requirement: Maintainer and compatibility documentation
The repository MUST document contributor instructions, architecture, exact protocol compatibility, security defaults, implementation status and a quick start. Documentation MUST identify the fixed Schema commit, supported methods, unsupported legacy behavior and all deferred v0.1 capabilities.

#### Scenario: Integrator checks compatibility
- **WHEN** an integrator reads the protocol compatibility documentation
- **THEN** they can determine the exact protocol version and Schema commit, required HTTP behavior, supported methods and unsupported features without inspecting source code
