# prestashopheaven/build-rules-core

Core of the Module-Factory rules registry: the rule schema, the loader that reads every rules package, the stage filter, the conventions.md generator and the ps-module rules command.

## Use when

When a stage of the Module-Factory needs its rules as a checklist (ps-module rules stage <stage>), when a rule is added or changed (edit the JSON, then ps-module rules generate), or to check that every rule has a stage and a check.

## Install

```
composer require prestashopheaven/build-rules-core
```

## Usage

<!-- Examples of the public API. -->

---

Read-only split of [`prestashopheaven/mono-build-rules`](https://github.com/prestashopheaven/mono-build-rules): changes,
issues and pull requests go there. MIT License.
