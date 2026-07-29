## ADDED Requirements

### Requirement: Fixed modern protocol baseline
The Server MUST implement only MCP protocol version `2026-07-28` as defined by official Schema commit `271ecc9accafdd9b83a3c869fa67c22953b2af80`. It MUST NOT probe, negotiate down to or execute a legacy protocol version.

#### Scenario: Supported protocol version is supplied
- **WHEN** a request declares protocol version `2026-07-28`
- **THEN** the request proceeds to the remaining protocol validation steps

#### Scenario: Unsupported protocol version is supplied
- **WHEN** a request declares any other protocol version
- **THEN** the Server returns JSON-RPC error `UnsupportedProtocolVersion` with code `-32022` and does not dispatch the method

### Requirement: Strict single-message JSON-RPC parsing
The protocol parser MUST accept exactly one JSON-RPC 2.0 request or notification object and MUST reject malformed JSON, non-object top-level values and batch arrays with the applicable standard JSON-RPC error.

#### Scenario: Batch request is received
- **WHEN** the decoded top-level JSON value is an array
- **THEN** the parser rejects it without dispatching any array member

#### Scenario: Malformed JSON is received
- **WHEN** the body is not valid JSON
- **THEN** the Server returns the standard JSON-RPC parse error

### Requirement: Request identifier types
A request ID MUST be a string or integer. A request with a `null`, float, boolean, object or array ID MUST be rejected as invalid, while a message with no ID MUST be treated as a notification.

#### Scenario: Integer ID is supplied
- **WHEN** a valid request uses an integer as its ID
- **THEN** the Server preserves that ID in the response

#### Scenario: Fractional ID is supplied
- **WHEN** a request uses a float as its ID
- **THEN** the Server rejects the message as an invalid request

#### Scenario: Null ID is supplied
- **WHEN** a message explicitly includes `id: null`
- **THEN** the Server rejects the message as an invalid request rather than treating it as a notification

### Requirement: Required request metadata
Every request and notification MUST include the protocol version and client capability metadata required by the fixed Schema, including client information and client capabilities where required by that Schema. Missing or structurally invalid metadata MUST prevent method dispatch.

#### Scenario: Client capabilities are absent
- **WHEN** an otherwise valid message omits required client capability metadata
- **THEN** the Server returns the applicable invalid-request or invalid-params JSON-RPC error and does not invoke a handler

### Requirement: Supported Server methods
The v0.1 protocol driver MUST dispatch `server/discover`, `tools/list` and `tools/call`, and MUST return the standard method-not-found error for every other method, including `initialize`.

#### Scenario: Discovery is requested
- **WHEN** a valid authorized request calls `server/discover`
- **THEN** the Server returns the protocol version, Server identity, supported capabilities and optional instructions allowed for that Principal

#### Scenario: Legacy initialize is requested
- **WHEN** a valid message calls `initialize`
- **THEN** the Server returns method-not-found and creates no session

### Requirement: Deterministic protocol errors
The protocol layer MUST provide standard JSON-RPC parse, invalid-request, method-not-found, invalid-params and internal errors, plus `HeaderMismatch` code `-32020` and `UnsupportedProtocolVersion` code `-32022`. Error responses MUST retain a valid request ID when one can be safely identified and MUST omit the optional ID when it cannot, as required by the fixed Schema.

#### Scenario: Header and body disagree
- **WHEN** transport metadata conflicts with the parsed protocol message
- **THEN** the Server returns `HeaderMismatch` with code `-32020` and does not dispatch the message

### Requirement: Dispatch result model
Protocol dispatch MUST return one of `JsonResult`, `AcceptedResult` or `StreamResult`; v0.1 production paths MUST return `JsonResult` for requests and `AcceptedResult` for notifications and MUST NOT produce `StreamResult`.

#### Scenario: Notification completes
- **WHEN** a valid notification has been processed
- **THEN** dispatch produces `AcceptedResult` without a JSON-RPC response envelope
