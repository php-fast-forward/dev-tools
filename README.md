# FastForward\DevTools

Fast Forward DevTools is a standalone PHP quality and repository automation CLI.
Run it through CPX to share tooling without adding it to every library's
Composer dependency graph.

[![PHP Version](https://img.shields.io/badge/php-^8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/releases/)
[![Composer Package](https://img.shields.io/badge/composer-fast--forward%2Fdev--tools-F28D1A.svg?logo=composer&logoColor=white)](https://packagist.org/packages/fast-forward/dev-tools)
[![Tests](https://img.shields.io/github/actions/workflow/status/php-fast-forward/dev-tools/tests.yml?logo=githubactions&logoColor=white&label=tests&color=22C55E)](https://github.com/php-fast-forward/dev-tools/actions/workflows/tests.yml)
[![Coverage](https://img.shields.io/badge/coverage-phpunit-4ADE80?logo=php&logoColor=white)](https://php-fast-forward.github.io/dev-tools/coverage/index.html)
[![Metrics](https://img.shields.io/badge/metrics-phpmetrics-8B5CF6?logo=php&logoColor=white)](https://php-fast-forward.github.io/dev-tools/metrics/index.html)
[![Docs](https://img.shields.io/github/deployments/php-fast-forward/dev-tools/github-pages?logo=readthedocs&logoColor=white&label=docs&labelColor=1E293B&color=38BDF8&style=flat)](https://php-fast-forward.github.io/dev-tools/index.html)
[![License](https://img.shields.io/github/license/php-fast-forward/dev-tools?color=64748B)](LICENSE)
[![GitHub Sponsors](https://img.shields.io/github/sponsors/php-fast-forward?logo=githubsponsors&logoColor=white&color=EC4899)](https://github.com/sponsors/php-fast-forward)

<p align="center">
  <img src="docs/_static/mascot-banner.png" alt="Dash coordinating tests, refactoring, and documentation at a multi-monitor DevTools workstation" width="840">
</p>

## ✨ Features

- Runs refactoring, PHPDoc, code style, tests, dependency analysis and reporting.
- Uses consumer configuration first and packaged defaults when it is absent.
- Executes with its own runtime and an explicit consumer working directory.
- Exports `fast-forward-*` skills under `resources/skills` for LLM Skills.
- Provides explicit repository synchronization for metadata, workflow callers,
  editor defaults, Git hooks, ignore rules, licensing and local Wiki Markdown.
- Delegates changelog and release policy to the independent Fast Forward Changelog.

## 🚀 Run with CPX

Install [CPX](https://github.com/laravel/cpx) and choose an exact published
DevTools 2.x version. Set `DEV_TOOLS_VERSION` to its numeric version. These
examples describe the version 2 interface; verify release availability before
migrating a consumer. Install the consumer's own Composer dependencies normally.

```bash
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" tests
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" code-style
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" standards --fix
```

`--skip-local` chooses CPX's separate installation. DevTools does not need to
appear in the consumer's `require-dev`. Its tool runtime PHP requirements are
separate from the consumer library's compatibility matrix.

## 🛠️ Usage

```bash
# Inspect the selected CLI and runtime metadata
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" list
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" runtime --json --no-ansi --no-interaction

# Run against a different checkout and select an artifact workspace
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" --working-dir=/path/to/project tests
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" --workspace-dir=.artifacts reports

# Preview and explicitly apply repository synchronization
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" dev-tools:sync --dry-run
cpx --skip-local "fast-forward/dev-tools:$DEV_TOOLS_VERSION" dev-tools:sync
```

Use `--json` or `--pretty-json` for structured results and `--progress` for
human-readable progress. `--pretty-json` remains valid JSON without ANSI escapes.
The runtime command reports `binary`, `autoload`, `package_root`, `project_root`
and `version`, allowing workflows to discover actual runtime paths.

## 🧰 Command Summary

| Command | Purpose |
|---------|---------|
| `standards` | Full quality pipeline; default command. |
| `tests` | PHPUnit with consumer-first configuration and optional coverage. |
| `code-style` | Composer Normalize and ECS; checks do not refresh the lockfile. |
| `refactor` / `phpdoc` | Rector and PHPDoc quality checks and fixes. |
| `dependencies` | Missing, unused, misplaced and outdated dependency analysis. |
| `metrics` | PhpMetrics reports. |
| `docs` / `wiki` / `reports` | HTML, Wiki Markdown and combined reports. |
| `dev-tools:sync` | Explicit metadata and repository asset synchronization. |
| `funding` / `codeowners` | Funding and ownership metadata. |
| `gitignore` / `gitattributes` / `license` / `git-hooks` | Repository defaults. |
| `runtime` | Machine-readable paths and version for automation. |

Run each command after the CPX package selector, or use `php bin/dev-tools`
when developing this repository. `help <command>` exposes its current options.

`self-update` refreshes CPX caches for non-global execution. It does not change
the consumer Composer graph or reviewed exact pins. Adopting a newer release
requires updating the selected script, workflow and skill-source versions.

## 🔌 Skills and Integration

[LLM Skills](https://github.com/roxblnfk/skills) installs procedural skills
independently from the executable. DevTools exports its owned skill packages
under `resources/skills/fast-forward-*`; names remain stable when responsibilities
move into dedicated repositories. See the [skill distribution guide](docs/usage/syncing-packaged-skills.rst).

Use Composer Trusted only for packages actually installed in the consumer.
Use a pinned GitHub source for external tools that are absent from its Composer
graph. Each skill has one owner. Missing trusted donor payloads are errors to
resolve, not permission to fetch a newer fallback silently.

DevTools no longer registers a Composer plugin, install/update hooks, or the
`skills` and `agents` commands. LLM Skills does not synchronize generic workflow
files, metadata or arbitrary project-agent prompts. `dev-tools:sync` remains an
explicit operation for the repository assets still owned here. Existing custom
files and Dependabot policy require review during migration.

## 🏗️ Architecture

The CLI loads its own installation's autoloader. The shared service provider
wires lazy Symfony commands, process execution, project metadata, filesystem
services and reports. Consumer source and configuration are selected through
the working directory. CPX separates dependency resolution, so a config or
container library can use DevTools while DevTools depends on its released runtime.
Public test APIs still need compatible consumer dependencies; isolation does not
make incompatible class definitions interchangeable within one process.

## 🛠️ Version 2 Migration

Follow the [migration and extraction strategy](docs/usage/dev-tools-2-migration.rst).
Publish and verify DevTools 2 first, then migrate each consumer in a separate PR:
pin the external runtime and skill sources, remove DevTools from `require-dev`,
replace plugin commands and local vendor paths, and verify the actual checks.
The guide also proposes issue triage without changing issue state.

## 🤝 Contributing

```bash
composer install
php bin/dev-tools tests
composer dev-tools
```

Update README and docs when commands or consumer behavior change. Add an
independent `.changelog/*.md` fragment and validate it using the selected release
of [Fast Forward Changelog](https://github.com/php-fast-forward/changelog).
Release preparation remains separate from merge and publication authority.

## 🛡 License

MIT; see [LICENSE](LICENSE).

## 🔗 Links

- [Repository](https://github.com/php-fast-forward/dev-tools)
- [Packagist](https://packagist.org/packages/fast-forward/dev-tools)
- [Documentation](docs/index.rst)
- [Workflow guide](docs/usage/github-actions.rst)
