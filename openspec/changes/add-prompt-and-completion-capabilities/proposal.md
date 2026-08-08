## Why

Clients need server-provided reusable interaction templates and argument suggestions in addition to Tools and Resources. Adding Prompts and Completion together provides a coherent authoring and discovery experience while keeping Client-side sampling and elicitation out of scope.

## What Changes

- Add immutable Prompt definitions, argument definitions, messages, and content DTOs.
- Add per-request Prompt handlers and independent list/get authorization.
- Route official `prompts/list` and `prompts/get` requests with cursor pagination and argument validation.
- Add Completion providers for official `completion/complete` requests against registered Prompt and Resource Template references.
- Advertise Prompt and Completion capabilities only when available to the current Principal.
- Add official Schema conformance, runtime, isolation, examples, and documentation coverage.

## Capabilities

### New Capabilities

- `prompt-runtime`: Defines Prompt registration, authorized discovery, argument validation, and message rendering.
- `completion-runtime`: Defines bounded completion suggestions for registered Prompt and Resource Template arguments.
- `prompt-completion-protocol`: Defines Prompt and Completion routing and discovery advertisement.

### Modified Capabilities

None.

## Impact

This extends public SDK contracts and DTOs, Server definitions, registry validation, protocol routing, authorization boundaries, examples, tests, and compatibility documentation. It depends on Resource Templates from `add-resource-capabilities` for Resource-reference completion.
