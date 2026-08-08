## 1. Official Contract and DTOs

- [x] 1.1 Map pinned official Resource request, result, content, annotation, and pagination definitions into tests
- [x] 1.2 Add strict SDK Resource, Resource Template, content, page, call, and result DTOs
- [x] 1.3 Add Resource handler, resolver, and authorization contracts using SDK-owned types only

## 2. Registry and Runtime

- [x] 2.1 Extend Server definitions and registry validation with immutable Resources and Templates
- [x] 2.2 Implement opaque definition-bound cursor pagination
- [x] 2.3 Implement Principal-filtered Resource and Template listing without metadata leakage
- [x] 2.4 Implement authorized exact/template reads with fresh handler resolution and output bounds
- [x] 2.5 Add duplicate, URI/template validation, cursor, authorization, handler isolation, and sanitized failure tests

## 3. Protocol and Webman Integration

- [x] 3.1 Route `resources/list`, `resources/read`, and `resources/templates/list` in the native driver
- [x] 3.2 Advertise Principal-visible Resource capabilities in discovery
- [x] 3.3 Add Header mirror handling and official Schema request/result fixtures for Resource methods
- [x] 3.4 Wire Resource resolvers through Webman without caching handlers

## 4. Examples and Acceptance

- [x] 4.1 Add a strict Resource example and end-to-end discovery/list/template/read coverage
- [x] 4.2 Update README, architecture, security, compatibility, implementation status, and AGENTS protocol boundaries
- [x] 4.3 Run focused tests and `composer check`
