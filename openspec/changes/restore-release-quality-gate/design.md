## Context

Git currently has no attributes file. On Windows, `core.autocrlf` converts the pinned JSON Schema and selected PHP files to CRLF: the fixture grows by exactly one byte for each of its 3,963 lines, its digest changes, Mago reports whole-file diffs, and the release gate stops before lint, analysis, and tests.

## Goals / Non-Goals

**Goals:**

- Make maintained source and the official fixture byte-stable across platforms.
- Run the same `composer check` locally and in CI on every supported PHP version.
- Preserve exact fixture size and SHA-256 assertions.

**Non-Goals:**

- Changing protocol behavior, dependencies, or public SDK APIs.
- Hiding failures with a baseline or weaker integrity assertions.

## Decisions

- Add `.gitattributes` with LF for maintained text and explicit binary treatment for assets that must never be normalized. The official JSON Schema remains UTF-8 JSON with LF so it is readable and byte-stable.
- Normalize only repository-maintained files; preserve unrelated `test.php` worktree state.
- Add a GitHub Actions matrix for supported PHP minors and run `composer validate --strict` plus `composer check`.
- Keep one Composer quality gate as the source of truth instead of duplicating tool options in CI.

## Risks / Trade-offs

- [Existing clones retain CRLF until files are refreshed] -> Document one-time renormalization and commit normalized bytes.
- [A future fixture download changes bytes] -> Require explicit size, digest, source commit, and conformance updates in its own protocol change.
- [CI matrix increases runtime] -> Cache Composer downloads while running the complete gate on every matrix entry.

## Migration Plan

Normalize affected maintained files, verify the fixture, add CI, and run `composer check`. Rollback removes the attributes and CI files but is not recommended because it restores platform-dependent behavior.

## Open Questions

None.
