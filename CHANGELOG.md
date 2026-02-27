## [v1.1.1] - 2026-02-27

### Fixed
- Fixed provider registration flow to always merge package config before `isEnabled()` checks.
- Prevented overriding existing `logging.channels.otel` user configuration.
- Added endpoint validation before OTLP send attempts.
- Corrected OTEL severity mapping for Monolog levels.
- Fixed payload builder constructor wiring and global attribute handling.
- Avoided duplicate `trace_id` and `span_id` in OTLP attributes.
- Improved runtime resilience when resolving app/config metadata.

### Compatibility
- Kept runtime package compatibility for Laravel 8-12 and Monolog 2/3.
- Updated development constraints to current tested toolchain.

### Tests
- Added unit tests for formatter, payload builder, severity mapper, and service provider behavior.
