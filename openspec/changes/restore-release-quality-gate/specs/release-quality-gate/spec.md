## ADDED Requirements

### Requirement: Cross-platform byte stability
The repository SHALL declare line-ending rules that preserve maintained PHP files and the pinned official Schema fixture byte-for-byte across supported platforms.

#### Scenario: Windows checkout
- **WHEN** the repository is checked out with automatic CRLF conversion enabled
- **THEN** maintained PHP files and the official Schema SHALL retain the repository's LF bytes

### Requirement: Pinned fixture integrity
The quality gate MUST verify the official Schema source commit, exact byte size, and SHA-256 digest without network access.

#### Scenario: Fixture byte changes
- **WHEN** any byte in the pinned official Schema differs
- **THEN** the integrity test SHALL fail before release

### Requirement: Unified quality gate
The repository SHALL use `composer check` as the single complete formatting, linting, analysis, and Pest verification command without a baseline.

#### Scenario: Clean checkout verification
- **WHEN** a maintainer runs `composer check` from a clean supported checkout
- **THEN** every configured quality stage SHALL run successfully

### Requirement: Continuous integration matrix
Continuous integration SHALL validate Composer metadata and run the unified quality gate on every supported PHP minor version.

#### Scenario: Pull request validation
- **WHEN** a pull request changes maintained repository files
- **THEN** CI SHALL report the Composer validation and quality-gate result for every supported PHP version
