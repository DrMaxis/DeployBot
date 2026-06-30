# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- _Nothing yet._

### Changed

- _Nothing yet._

### Fixed

- _Nothing yet._

## [0.1.0] - 2026-06-30

Initial open-source release.

### Added

- HMAC signature verification for incoming Slack webhooks (constant-time
  compare, configurable timestamp-skew replay guard).
- Pluggable slash-command dispatcher with admin allowlisting, exception
  capture, and a built-in `help` command that introspects host registrations.
- Slack Web API client wrapping `chat.postMessage` and `chat.postEphemeral`.
- Discord webhook client supporting multi-purpose webhook URL routing.
- Channel-subscription model + migration for tracking which Slack channels
  want which product events.
- Block Kit response envelope types so handler code can return structured
  responses without hand-assembling JSON.
- Laravel 11, 12, and 13 compatibility (`composer.json` advertises `^11 || ^12 || ^13`).
- Auto-discovered Laravel service provider (no manual registration needed).
- Publishable config file with reasonable defaults.

[Unreleased]: https://github.com/DrMaxis/DeployBot/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/DrMaxis/DeployBot/releases/tag/v0.1.0
