## ADDED Requirements

### Requirement: Complete Server definitions
Each Server definition MUST provide a unique ID, unique endpoint path, Server identity information, Tool collection, authenticator, authorizer and Origin policy. The registry MUST expose definitions as immutable values after construction.

#### Scenario: Registry resolves a Server
- **WHEN** a request route is matched to a registered path
- **THEN** the registry returns exactly one immutable Server definition containing all required policies and identity data

### Requirement: Startup uniqueness validation
The registry MUST fail Worker startup when Server IDs, endpoint paths or Tool names within a Server are duplicated. Validation MUST complete before any Server endpoint begins accepting requests.

#### Scenario: Two Servers share a path
- **WHEN** configuration assigns the same normalized endpoint path to two Server IDs
- **THEN** registry construction fails with an actionable configuration error before the Worker serves traffic

#### Scenario: Tool name is duplicated within a Server
- **WHEN** two configured Tools for one Server declare the same name
- **THEN** registry construction fails before either Tool can be listed or called

### Requirement: Deny-by-default authentication
A Server without an explicitly configured authenticator MUST use `DenyAllAuthenticator`. Anonymous requests MUST be accepted only when the Server explicitly selects `AllowAnonymousAuthenticator`.

#### Scenario: Default configuration receives anonymous request
- **WHEN** an anonymous request reaches a Server with no explicit authenticator
- **THEN** authentication is denied before discovery or Tool dispatch

#### Scenario: Server explicitly allows anonymous access
- **WHEN** a Server selects `AllowAnonymousAuthenticator` and receives an otherwise valid anonymous request
- **THEN** the request receives an anonymous Principal and proceeds to authorization

### Requirement: Safe Worker-lifetime caching
The registry MAY cache immutable Tool definitions and locally compiled Schema data for the Worker lifetime, but MUST NOT cache Tool handler instances, Principal data, `ExecutionContext` objects or transport request data across requests.

#### Scenario: Two calls use the same Tool definition
- **WHEN** two requests call the same Tool in one Worker
- **THEN** they may share immutable definition data but receive independently resolved handlers and distinct request contexts

### Requirement: Request and Worker isolation
Concurrent or interleaved requests in one Worker and equivalent requests across multiple Workers MUST not observe another request's Principal, trace ID, capabilities, deadline, arguments or handler state through SDK-managed mutable state.

#### Scenario: Interleaved users invoke one Tool
- **WHEN** requests from two Principals are interleaved in a long-running Worker
- **THEN** each authorization and Tool call observes only its own Principal and execution metadata

#### Scenario: Multiple Workers load one configuration
- **WHEN** several Workers construct registries from the same application configuration
- **THEN** each Worker has equivalent immutable definitions and no implicit cross-Worker request state
