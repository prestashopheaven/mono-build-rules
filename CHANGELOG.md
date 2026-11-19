# Changelog

All packages of this monorepo share one version. Per package details: its own `CHANGELOG.md`.

## 1.3.0

- `build-rules-quality`: TST-15, sending and queue tests use public example URLs (`PublicHost` of build-testing-php)
  or assert the skip of the test shop host.

## 1.2.0

- `build-rules-frontend`: TPL-30, never select on classes PrestaShop builds from translated texts (grid row links).

## 1.1.1

- `build-rules-process`: REL-08 without the Addons step (the Addons upload and the Cloudflare purge are manual).

## 1.1.0

- `build-rules-core`: the stage order is intake, research, design, build, content, verify, release, retro (content
  before verify, like the factory in build-tools 1.2.0).

## 1.0.0

- Monorepo created (2026-11-18).
