# Description Patterns

## How to Write Human-Readable Descriptions

Rule: Describe the IMPACT, not the IMPLEMENTATION.

## Checklist

- Keep each entry to one line.
- Name the surface that changed: class, command, option, workflow, API, or config.
- Describe the user-visible effect first.
- Avoid implementation verbs such as `extract`, `rename`, `refactor`, or `reorganize` unless the refactor itself changes behavior.

## Transformation Examples

### Bad → Good

```
Bad: "feat: add bootstrap"
Good: "Added `Bootstrapper` class to create CHANGELOG.md when missing"

Bad: "refactor: extract to new class"
Good: "Changed changelog generation to classify release entries by observed diff impact"

Bad: "fix: validate unreleased notes"
Good: "Fixed validation of unreleased changelog entries"

Bad: "chore: update dependencies"
Good: N/A - Skip infrastructure-only changes
```

## Description templates

Use these patterns when they fit the diff:

```markdown
- Added `<surface>` to `<do what>` for `<benefit>`
- Changed `<surface>` to `<new behavior>`
- Fixed `<failure mode>` in `<surface>`
- Removed deprecated `<surface>`
- Deprecated `<surface>`; use `<replacement>`
```

## Concrete examples

```markdown
- Added `changelog:init` to bootstrap `.keep-a-changelog.ini` and `CHANGELOG.md`
- Changed changelog sync to install reusable release-note workflows
- Fixed bootstrap of the `Unreleased` section for existing changelog files
- Removed deprecated `LegacyCommand`
- Deprecated `Parser::process()`; use `Renderer::render()` instead
```

## Optional references

Append issue or PR references only when they add useful context and the diff already supports the entry:

```markdown
- Added changelog automation (#40)
- Changed workflow to use PHP 8.3 (#31)
- Fixed validation bug (#42)
```

When a matching pull request exists, prefer appending the PR reference in the format `(#123)` at the end of the line.

Do not rely on the PR text as the source of truth.
