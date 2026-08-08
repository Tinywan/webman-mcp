## Context

The existing architecture caches immutable Server and Tool definitions per Worker while resolving Tool handlers per call. Resources need the same isolation model, URI-oriented lookup, official result shapes, pagination, and independent list/read authorization.

## Goals / Non-Goals

**Goals:**

- Implement official Resource list, template list, and read server methods.
- Keep public contracts free of Webman and Opis types.
- Prevent hidden Resource metadata and content from leaking.

**Non-Goals:**

- Filesystem or HTTP fetching built into the SDK.
- Resource subscriptions or change notifications, which belong to the notification change.
- Mutable Worker-level Resource providers.

## Decisions

- Add immutable `ResourceDefinition`, `ResourceTemplateDefinition`, `RegisteredResource`, and content DTOs. Definitions are cached; handlers are resolved per read.
- Exact Resources use normalized absolute URI strings. Templates use RFC 6570-compatible URI template strings as opaque registration keys; applications perform expansion in handlers.
- Extend authorization with dedicated Resource list/read and Template list checks through a new Resource authorizer contract, leaving Tool authorizers source-compatible.
- Use opaque base64url cursors containing a validated offset and definition-set fingerprint. Invalid or stale cursors return invalid params.
- A read of unknown or unauthorized URI returns one indistinguishable invalid-params response.
- Advertise Resources only when at least one Resource or Template is visible to the Principal.

## Risks / Trade-offs

- [URI templates are complex] -> Validate syntax at registration but keep expansion application-owned.
- [Offset cursors can become stale] -> Include an immutable registry fingerprint and reject mismatches.
- [Large content exhausts memory] -> Bound aggregate response bytes through Server options and later governance controls.

## Migration Plan

All new constructor inputs are optional and default empty. Existing Tool-only Servers retain identical discovery and routing behavior.

## Open Questions

None.
