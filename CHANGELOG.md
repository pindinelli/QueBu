# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-05-28

### Added
- Security policy (`SECURITY.md`) for vulnerability reporting.
- Stronger identifier validation and quoting via `IdentifierQuoter`.
- Protection against unsafe write operations (`UPDATE`/`DELETE` without `WHERE`) unless explicitly enabled.
- Query contracts split into dedicated interfaces under `src/Contracts`.
- DTO and enum usage across query composition paths.
- Expanded documentation and example coverage for core API usage.

### Changed
- Join API alignment across builder usage and docs.
- Internal query-building consistency around identifiers and condition handling.
- `DatabaseConfig` environment factory naming and usage improved (`fromEnvironment()`).

### Security
- Improved defense against SQL injection vectors in dynamic identifiers.
- Reinforced prepared-statement usage for values.
