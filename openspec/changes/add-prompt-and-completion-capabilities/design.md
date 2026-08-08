## Context

Prompt rendering and Completion are related but distinct server functions. Both reference immutable definitions, perform request-specific work, and need authorization before names, arguments, or suggestions are exposed.

## Goals / Non-Goals

**Goals:**

- Implement official Prompt list/get and Completion methods.
- Validate Prompt arguments deterministically.
- Support completion for Prompt arguments and Resource Template variables.

**Non-Goals:**

- Client-side sampling, roots, elicitation, or model execution.
- Automatically constructing Prompts from PHP reflection.
- Unbounded fuzzy search.

## Decisions

- Cache immutable Prompt definitions and resolve Prompt renderers per get call.
- Model Prompt messages with SDK-owned role and content DTOs; reuse Resource content DTOs only where the official schema permits embedded resources.
- Keep Prompt authorization separate for list, get, and completion so a known hidden name cannot be rendered or completed.
- Completion providers receive a typed reference, argument name, current value, optional context, Principal, and deadline. Providers return at most the configured maximum items.
- Validate required and declared Prompt arguments before resolving a renderer. Unknown arguments are rejected unless the definition explicitly allows them.
- Use the same opaque pagination utility introduced for Resources.

## Risks / Trade-offs

- [Completion handlers become slow] -> Enforce deadlines and bounded result counts.
- [Content union grows public API] -> Use small final readonly DTOs behind an SDK interface.
- [Resource dependency is absent] -> Apply this change only after Resource Templates are implemented.

## Migration Plan

New Server inputs default empty. Existing Servers do not advertise Prompts or Completion and remain compatible.

## Open Questions

None.
