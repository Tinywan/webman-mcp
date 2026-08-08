# Contributing and Release Checks

## Checkout

The repository pins LF line endings in `.gitattributes`. This includes maintained PHP files and the
official MCP Schema fixture, whose exact bytes are part of the offline conformance contract. Existing
Windows clones created before these attributes were added should refresh tracked files before
verifying a release; do not change the fixture size or digest to match a CRLF checkout.

## Verification

Install dependencies and run the single release gate from the repository root:

```bash
composer install
composer validate --strict --no-check-publish
composer check
```

`composer check` runs Mago formatting, linting, analysis, and the complete Pest suite without a
baseline. CI runs the same commands on every supported PHP minor version.

## Protocol Fixtures

An official Schema update requires a separate OpenSpec change. Update the source commit, exact byte
size, SHA-256 digest, protocol documentation, and offline conformance tests together. Runtime code and
tests must not fetch Schema files from the network.
