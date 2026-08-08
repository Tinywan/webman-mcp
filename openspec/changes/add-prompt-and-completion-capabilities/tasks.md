## 1. Official Contract and DTOs

- [x] 1.1 Map pinned official Prompt and Completion request/result definitions into conformance tests
- [x] 1.2 Add strict Prompt definition, argument, message, content, call, and result DTOs
- [x] 1.3 Add Prompt renderer/resolver and Completion provider/resolver contracts using SDK-owned types only

## 2. Registry and Runtime

- [x] 2.1 Extend Server definitions and registry validation with immutable Prompt and Completion registrations
- [x] 2.2 Implement independent Prompt list/get/completion authorization boundaries
- [x] 2.3 Implement Prompt pagination, argument validation, fresh rendering, and sanitized failures
- [x] 2.4 Implement bounded Prompt and Resource Template completion with deadline enforcement
- [x] 2.5 Add duplicate, visibility, arguments, isolation, limits, deadline, and failure tests

## 3. Protocol and Integration

- [x] 3.1 Route `prompts/list`, `prompts/get`, and `completion/complete` in the native driver
- [x] 3.2 Advertise Principal-visible Prompt and Completion capabilities in discovery
- [x] 3.3 Add Header mirror handling and official Schema fixtures for Prompt and Completion methods
- [x] 3.4 Wire resolvers through Webman without caching renderers or providers

## 4. Examples and Acceptance

- [x] 4.1 Add strict Prompt and Completion examples with end-to-end tests
- [x] 4.2 Update README, architecture, security, compatibility, implementation status, and AGENTS protocol boundaries
- [x] 4.3 Run focused tests and `composer check`
