## ADDED Requirements

### Requirement: Dedicated stateless Server endpoints
Each configured Server MUST expose its own route and MUST accept MCP messages only via POST. The transport MUST NOT create or consume PHP sessions or `Mcp-Session-Id` state. Incoming `Mcp-Session-Id` and `Last-Event-ID` headers MUST be ignored and MUST NOT be minted or echoed.

#### Scenario: Request is sent to one Server route
- **WHEN** a POST is sent to a configured Server path
- **THEN** only that Server definition, identity, authentication policy and Tool registry are eligible for dispatch

#### Scenario: Legacy session header is supplied
- **WHEN** a request includes `Mcp-Session-Id` or `Last-Event-ID`
- **THEN** the transport ignores the legacy header, processes the modern request normally, and neither persists nor echoes session or resume state

### Requirement: HTTP method enforcement
The endpoint MUST accept POST and MUST return HTTP 405 with an `Allow: POST` header for GET, DELETE and other unsupported methods.

#### Scenario: GET is sent to MCP endpoint
- **WHEN** a client sends GET to a configured MCP route
- **THEN** the Server returns HTTP 405, advertises POST and does not invoke protocol dispatch

### Requirement: Media type negotiation
POST requests MUST use the JSON content type required by the fixed protocol Schema and MUST advertise acceptance of both JSON and event-stream response media types. Header token and parameter comparisons MUST be case-insensitive where HTTP requires it.

#### Scenario: Accept omits one required response type
- **WHEN** a POST request does not advertise both required media types
- **THEN** the transport rejects it before authentication or protocol dispatch

#### Scenario: Header casing differs
- **WHEN** valid media types are sent using different Header-name or media-type casing
- **THEN** the transport treats them as equivalent according to HTTP rules

### Requirement: Request size and Origin validation
The transport MUST enforce a default maximum body size of 2 MiB and MUST validate an Origin against the selected Server's configured policy before decoding or dispatching the body.

#### Scenario: Body exceeds the limit
- **WHEN** the declared or observed request body exceeds 2 MiB without a lower configured limit
- **THEN** the transport rejects the request without parsing or dispatching the JSON

#### Scenario: Origin is not allowed
- **WHEN** a request Origin is outside the selected Server's allowlist
- **THEN** the transport rejects the request before invoking authentication or a Tool

### Requirement: Protocol Header and body consistency
The transport MUST validate the protocol version Header plus `Mcp-Method` and `Mcp-Name` against the corresponding body values. A missing required Header or any normalized mismatch MUST produce `HeaderMismatch -32020` and MUST prevent dispatch.

#### Scenario: Method Header differs from body
- **WHEN** `Mcp-Method` names a different method from the JSON-RPC body
- **THEN** the Server returns `HeaderMismatch -32020`

### Requirement: Parameter Header decoding
The transport MUST support `Mcp-Param-*` and `x-mcp-header` metadata defined by the fixed protocol Schema, including its Base64 sentinel representation for values that cannot be represented directly. It MUST preserve valid non-ASCII values after decoding and MUST reject malformed Base64 or conflicting mirrored values.

#### Scenario: Non-ASCII parameter uses Base64 sentinel
- **WHEN** a valid encoded Header mirrors a non-ASCII body parameter
- **THEN** the transport decodes it losslessly and accepts the mirror comparison

#### Scenario: Encoded mirror is malformed
- **WHEN** a sentinel value contains invalid Base64 or decodes to a value conflicting with the body
- **THEN** the transport rejects the request without dispatch

### Requirement: HTTP response mapping without SSE output
A successfully processed notification MUST return HTTP 202 without a JSON-RPC body. A request with an ID MUST return a JSON response containing one JSON-RPC envelope. No v0.1 response path MUST emit an SSE stream.

#### Scenario: Request with ID succeeds
- **WHEN** a valid request with an ID completes
- **THEN** the endpoint returns a JSON media type and exactly one JSON-RPC response envelope

#### Scenario: Notification succeeds
- **WHEN** a valid notification completes
- **THEN** the endpoint returns HTTP 202 and no JSON-RPC response body
