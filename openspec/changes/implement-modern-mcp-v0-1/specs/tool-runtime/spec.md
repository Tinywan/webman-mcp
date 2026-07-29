## ADDED Requirements

### Requirement: Tool definition contract
Every Tool MUST provide a unique name, description, JSON Schema 2020-12 input Schema and optional output Schema through `ToolDefinition`, and MUST execute through `ToolInterface::call(ToolCall, ExecutionContext): ToolResult`.

#### Scenario: Valid Tool is registered
- **WHEN** a Tool returns a complete definition with a valid object input Schema
- **THEN** the registry makes its immutable definition available for authorized discovery and invocation

### Requirement: Principal-filtered Tool listing
`tools/list` MUST include only Tool definitions for which the selected Server's authorizer returns true from `canList` for the current Principal. It MUST NOT reveal hidden Tool names, descriptions or Schemas.

#### Scenario: Principal can see a subset of Tools
- **WHEN** a Principal is authorized to list one of two registered Tools
- **THEN** `tools/list` returns only the authorized Tool definition

### Requirement: Independent call authorization
`tools/call` MUST resolve a Tool by exact name and MUST call `canCall` for the current Principal even if that Tool was previously returned by `tools/list`. Unknown and unauthorized Tools MUST NOT invoke a handler.

#### Scenario: Caller knows a hidden Tool name
- **WHEN** a Principal calls a registered Tool for which `canCall` returns false
- **THEN** the Server returns the defined protocol-level rejection without invoking or exposing the Tool handler

#### Scenario: Tool name is unknown
- **WHEN** `tools/call` names no Tool in the selected Server
- **THEN** the Server returns the defined protocol-level unknown-Tool error

### Requirement: Input argument validation
`tools/call` parameters MUST contain an argument object. Before invoking a handler, the runtime MUST validate that object against the Tool's input Schema using JSON Schema 2020-12 and MUST map invalid names, shapes or values to a protocol error.

#### Scenario: Arguments violate input Schema
- **WHEN** an authorized call supplies an argument object that fails the Tool input Schema
- **THEN** the Server returns an invalid-params protocol error and does not invoke the Tool

### Requirement: Local-only Schema references
Tool Schemas MUST allow references only within the same Schema document through local `$ref` and `$defs`. Registration or validation MUST reject network URLs, filesystem paths and any other external reference without attempting to resolve them.

#### Scenario: Schema contains a remote reference
- **WHEN** a Tool input or output Schema contains an HTTP or HTTPS `$ref`
- **THEN** Tool registration fails without performing a network request

#### Scenario: Schema uses local definition
- **WHEN** a Tool Schema references its own `$defs` with a fragment-only `$ref`
- **THEN** the schema is accepted if the referenced definition and overall Schema are valid

### Requirement: Structured output validation
When a Tool defines `outputSchema`, every returned `structuredContent` value MUST be validated against it before a response is emitted. A handler result that violates its declared output Schema MUST be converted to a sanitized internal/protocol error.

#### Scenario: Handler returns invalid structured content
- **WHEN** a Tool result includes `structuredContent` that fails the declared output Schema
- **THEN** the Server does not expose the invalid content and returns the defined sanitized error

### Requirement: Tool result semantics
A successful Tool result MUST set `resultType` to `complete`. A business failure MUST be returned as a Tool result with `isError: true`, while malformed protocol input, failed authorization and runtime contract violations MUST be JSON-RPC errors rather than Tool business results.

#### Scenario: Tool reports a business failure
- **WHEN** an authorized Tool handles a valid call but cannot complete the business operation
- **THEN** the JSON-RPC response contains a Tool result marked `isError: true` rather than a JSON-RPC error

#### Scenario: Tool completes successfully
- **WHEN** an authorized Tool returns a valid successful result
- **THEN** the response result has `resultType: "complete"` and `isError` is absent or false according to the fixed Schema

### Requirement: Exception safety and cooperative deadline
The runtime MUST pass the request deadline to every Tool through `ExecutionContext`. Unhandled handler exceptions and deadline failures MUST be converted to sanitized errors that do not expose stack traces, credentials, sensitive Principal attributes or raw internal exception messages.

#### Scenario: Handler throws an exception
- **WHEN** a Tool handler throws an unhandled exception
- **THEN** the caller receives a sanitized error and the diagnostic channel retains the trace ID for server-side correlation
