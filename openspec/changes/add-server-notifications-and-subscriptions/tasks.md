## 1. Notification and Lifecycle Foundation

- [x] 1.1 Map pinned official notification and subscription definitions into conformance tests
- [x] 1.2 Add strict notification envelope, event, subscription call, and acknowledgement DTOs
- [x] 1.3 Add cancellation token and progress reporter contracts with no-op request defaults
- [x] 1.4 Add subscription provider, resolver, authorizer, and process-local cancellation coordinator contracts

## 2. Subscription Runtime

- [x] 2.1 Register immutable subscription definitions while resolving providers per request
- [x] 2.2 Implement authorized listen dispatch with acknowledgement-first ordered delivery
- [x] 2.3 Enforce event count, byte, lifetime, backpressure, disconnect, and cancellation bounds
- [x] 2.4 Add authorization, order, bounds, disconnect, cancellation, isolation, and redaction tests

## 3. Transport

- [x] 3.1 Activate `StreamResult` for `subscriptions/listen` only and encode official event-stream notifications
- [x] 3.2 Map Webman streamed responses without sessions, replay IDs, GET/DELETE, or downgrade fallback
- [x] 3.3 Preserve existing JSON request and empty 202 notification paths
- [x] 3.4 Add official Schema and HTTP transport conformance tests

## 4. Acceptance

- [x] 4.1 Add a strict subscription example and progress/cancellation integration coverage
- [x] 4.2 Update README, architecture, security, compatibility, implementation status, and AGENTS protocol boundaries
- [x] 4.3 Run focused tests and `composer check`
