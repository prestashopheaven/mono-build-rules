# mono-build-rules

The rules of the PrestaHaven Module-Factory: every convention as a registry entry with its stage and how it is checked, split per area, with the generated conventions.md and the ps-module rules command.

A monorepo of the PrestaHaven package collection: every folder in `packages/` is one Composer package, published as a
read-only repo `prestashopheaven/build-<folder>` on Packagist. All packages of this monorepo share one version.
The plan, the rules and the other monorepos: `docs/packages/` in the PrestaHaven workspace.

## Packages

| Package | Use when |
|---|---|
| `prestashopheaven/build-rules-code` | When code of a module is designed or written: the rules of STR, MOD, INS, DB, PHP, DOC, HK, SEC, CMP, DEP, PKG and ARCH. |
| `prestashopheaven/build-rules-content` | When texts, translations, documentation or marketing of a module are written: the rules of TR, DOCS and MKT. |
| `prestashopheaven/build-rules-core` | When a stage of the Module-Factory needs its rules as a checklist (ps-module rules stage <stage>), when a rule is added or changed (edit the JSON, then ps-module rules generate), or to check that every rule has a stage and a check. |
| `prestashopheaven/build-rules-frontend` | When templates, JavaScript, back-office pages, front controllers or multistore behaviour of a module are built: the rules of TPL, BO, FC and MS. |
| `prestashopheaven/build-rules-process` | When a module is released, published, committed or worked on next to other sessions: the rules of REL, CTX, GIT, DEMO, PAR and UPD. |
| `prestashopheaven/build-rules-quality` | When tests are written or the definition of done runs: the rules of TST, TOOL, TEST and TS. |

(`ps-module mono packages` lists them; the package index in the workspace has every class.)

## Rules

`ps-module rules` (from `bin/rules.php` of `build-rules-core`):

```
rules.php validate                          load every rule, counts per stage and check
rules.php list [--stage=S] [--check=C]      one line per rule
rules.php show <ID>                         one rule as Markdown
rules.php stage <stage> [--review-only]     the checklist of a pipeline stage
rules.php generate --out=FILE [--check]     conventions.md (the factory's .claude/skills/ps-module/conventions.md)
```

A rule lives in `packages/rules-<area>/rules/<section>.json`: `id`, `marker` (V, T or empty), `stages` (intake, research,
design, build, verify, content, release, retro or all), `checks` (validator, tool, tool:<step> or review) and `markdown`.
Edit the JSON, never the generated conventions.md.

## Work on it

```
composer install
composer check                 # ps-module mono check: the definition of done of every package
ps-module mono package <dir> "<description>" "<use when>" <keywords>   # a new package
```

Changes are made here, never in a split repo.

## Release

1. Write the changes under `## X.Y.Z` in `CHANGELOG.md` (and in the `CHANGELOG.md` of each changed package).
2. `ps-module mono set-version X.Y.Z` (root version, branch aliases, internal constraints).
3. Commit `Release X.Y.Z - <notes>` on `main` and push.

The release workflow checks everything, tags `vX.Y.Z`, makes the GitHub release, splits every package to its repo with
the same tag and updates Packagist. Never tag by hand.
