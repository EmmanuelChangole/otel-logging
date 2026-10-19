## [v1.1.3] - 2026-10-19

### Added
- Optional `Authorization` header for OTLP export via `OTEL_EXPORTER_AUTH_HEADER` (`otel-logging.exporter.auth_header`), for collectors that require auth such as OpenObserve.
- Laravel 13 support (`illuminate/support` and `illuminate/log` `^13.0`).

### Changed
- When `OTEL_ENABLED=true` but `OTEL_EXPORTER_ENDPOINT` is empty, the handler now disables export and logs a single warning instead of reporting an error on every log call.

### Tests
- Added tests for the auth header, `Config::getAuthHeader()`, and the empty-endpoint behaviour.
- Allowed `orchestra/testbench` `^11.0` for testing against Laravel 13.

## [v1.1.2]

### Fixed
- Treat non-2xx exporter responses as failures and report them to the fallback logger.
- Validate trace/span IDs and derive OTLP flags safely.
- Handle `json_encode` errors for array attributes.

### CI
- Added GitHub Actions test and coverage workflow.

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
