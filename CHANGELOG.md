# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] — v3 (in development)

### Added

- PHP 8.2+ baseline. Drops support for PHP 5.4 through 8.1.
- PSR-4 autoload with root namespace `Nfe\` under `src/`.
- Renamed Composer package: `nfe/client-php` (was `nfe/nfe`). Both can coexist
  during migration. `nfe/nfe` is frozen at v2.5.
- Strict types enforced in every source file.
- Pest 3, PHPStan level 8, and PHP-CS-Fixer (PER-CS 2.0 + PHP 8.2 migration)
  wired in `require-dev` and enforced via CI.
- GitHub Actions CI matrix across PHP 8.2 / 8.3 / 8.4.
- OpenSpec workflow with 8 change proposals captured under `openspec/changes/`.

### Changed

- Repository now develops on the `v3` branch. `master` is frozen at v2.5.

### Removed

- `lib/NFe/*` (legacy classmap-autoloaded code).
- `test/simpletest/*` runner integration. Pest replaces SimpleTest.
- Vendored `composer.phar`.
- `.travis.yml` (replaced by `.github/workflows/ci.yml`).

## [2.5] and earlier

See git history on the `master` branch. The v2 line is no longer maintained.
