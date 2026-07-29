# MCP 2026-07-28 baseline

`2026-07-28-baseline.json` is the offline compatibility fixture used by this SDK.
It records the v0.1-relevant invariants from the official schema at commit
`271ecc9accafdd9b83a3c869fa67c22953b2af80` and the SHA-256 of the complete
181,474-byte upstream schema.

Runtime protocol handling never fetches a schema over the network. The source URL is
retained only so maintainers can independently reproduce the checksum when proposing a
future protocol upgrade.
