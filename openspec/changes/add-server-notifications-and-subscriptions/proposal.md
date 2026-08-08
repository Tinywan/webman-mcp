## Why

Static list responses cannot inform clients when server-owned Tools, Resources, or Prompts change, and long-running work has no standard progress or cancellation lifecycle. The pinned protocol provides notification and subscription contracts that should be implemented without reintroducing legacy sessions or protocol downgrade behavior.

## What Changes

- Add server notification DTOs for list changes, Resource updates, progress, messages, cancellation, and subscription acknowledgement.
- Route official `subscriptions/listen` requests through an explicit subscription provider.
- Add a transport-neutral outbound event stream result while keeping legacy GET/DELETE transport and session IDs unsupported.
- Add cancellation tokens and progress reporting to request execution context.
- Preserve immutable Worker-cached definitions by resolving event/subscription providers per request.
- Add lifecycle, authorization, disconnect, backpressure, and protocol conformance tests.

## Capabilities

### New Capabilities

- `server-notifications`: Defines typed server-originated notifications, progress, cancellation, and sanitized log messages.
- `server-subscriptions`: Defines authorized subscription listening, acknowledgement, disconnect, and backpressure behavior.
- `notification-transport`: Defines the production event-stream response boundary without legacy session or downgrade semantics.

### Modified Capabilities

None.

## Impact

This affects protocol result types, execution context, transport response mapping, Server configuration, provider contracts, tests, and operational documentation. It depends on the Resource and Prompt changes for their change-notification families and does not add Client request capabilities.
