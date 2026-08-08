## Why

The repository declares `composer check` as its release gate, but Windows line-ending conversion currently changes the pinned official Schema bytes and makes Mago report formatting drift. A reproducible checkout must pass the same integrity, formatting, analysis, and test checks on every supported platform before new protocol capabilities are added.

## What Changes

- Pin repository text and binary treatment so PHP sources use LF and the official Schema fixture remains byte-for-byte stable.
- Restore a green `composer check` without weakening the pinned fixture assertions or adding a Mago baseline.
- Add CI coverage for supported PHP versions and the complete Composer quality gate.
- Document the release verification contract and local Windows expectations.

## Capabilities

### New Capabilities

- `release-quality-gate`: Defines reproducible checkout, official fixture integrity, CI, and release verification requirements.

### Modified Capabilities

None.

## Impact

This affects Git attributes, CI configuration, maintained file formatting, official Schema fixture handling, Composer verification, and contributor documentation. It does not change the public SDK API or MCP behavior.
