# Security Policy

## Supported Versions

| Version | Supported |
|---------|-----------|
| 1.x     | ✅        |
| < 1.0   | ❌        |

## Reporting a Vulnerability

Please do **not** open a public issue for security vulnerabilities.

Report privately by email to: **pindinelli@gmail.com**

Include:
- A clear description of the issue
- Steps to reproduce / PoC
- Impact assessment
- Suggested fix (if available)

You will receive an acknowledgment within **72 hours**.

## Security Notes

Quebu uses prepared statements for query values and validates SQL identifiers.
`UPDATE` and `DELETE` without `WHERE` are blocked by default unless explicitly overridden with `allowUnsafeWrites(true)`.
