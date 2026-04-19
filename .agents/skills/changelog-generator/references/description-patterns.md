# Description Patterns

Describe the impact, not the internal implementation.

When a changelog entry maps to a specific pull request, append the pull request
reference at the end of the line, for example `(#123)`.

## Good patterns

```markdown
- Added `<surface>` to `<do what>` for `<benefit>`
- Changed `<surface>` to `<new behavior>`
- Fixed `<failure mode>` in `<surface>`
- Removed deprecated `<surface>`
- Deprecated `<surface>`; use `<replacement>`
- Added `<surface>` to `<do what>` for `<benefit>` `(#123)`
```

## Examples

```markdown
- Added changelog automation that bootstraps a managed `CHANGELOG.md` on first entry
- Added `changelog:entry` to append categorized notes to `Unreleased`
- Changed release preparation to read release notes directly from `CHANGELOG.md`
- Fixed changelog validation against the base branch
- Added pull request Pages previews for reports `(#55)`
```

## Avoid

- `misc improvements`
- `cleanup`
- `refactorings`
- implementation-only statements that say nothing about user-visible effect
