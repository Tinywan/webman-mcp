## 1. Repository Normalization

- [x] 1.1 Add `.gitattributes` rules for LF-maintained text and byte-stable fixtures
- [x] 1.2 Normalize the nine Mago-reported PHP files and pinned official Schema without touching unrelated worktree files
- [x] 1.3 Verify the official Schema size, SHA-256 digest, and pinned commit documentation

## 2. Continuous Integration

- [x] 2.1 Add a GitHub Actions PHP matrix that validates Composer metadata and runs `composer check`
- [x] 2.2 Document cross-platform checkout and release verification requirements

## 3. Verification

- [x] 3.1 Run format, lint, analysis, fixture integrity, and Pest checks independently
- [x] 3.2 Run `composer check` successfully from the normalized worktree
