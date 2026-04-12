# Description Patterns

## How to Write Human-Readable Descriptions

Rule: Describe the IMPACT, not the IMPLEMENTATION.

## Quality Criteria

| Criterion | Good | Bad |
|-----------|------|-----|
| Specific | "Added `ChangelogCheckCommand` to verifies that the changelog contains pending unreleased notes." | "Added new feature" |
| Short | One line | Paragraph |
| Self-sufficient | "Creates CHANGELOG.md with all current version released" | "Bootstrap support" |
| Actionable | "Added `--filter` option on TestsCommand to be able to filter test pattern classes" | "Improved CLI" |

## Transformation Examples

### Bad → Good

```
Bad: "feat: add bootstrap"
Good: "Added `Bootstrapper` class to create CHANGELOG.md when missing"

Bad: "refactor: extract to new class"
Good: "Extracted `CommitClassifier` for improved separation of concerns"

Bad: "fix: validate unreleased notes"
Good: "Fixed validation of unreleased changelog entries"

Bad: "chore: update dependencies"
Good: N/A - Skip infrastructure-only changes
```

## Class Names Pattern

Always include class/method names:

```markdown
- Added `Bootstrapper` to bootstrap changelog assets
- Added `MarkdownRenderer::render()` for generating output
- Changed `Config::load()` to accept optional path parameter
- Fixed `Parser::parse()` handling of empty input
```

## API Changes Pattern

```markdown
- Added `CommandInterface::execute()` method
- Changed `Parser::parse($input)` to accept optional `$options` array
- Removed deprecated `LegacyCommand`
- Deprecated `Parser::process()`, use `Renderer::render()` instead
```

## Reference Patterns (PR)

When changes came from a PR:

```markdown
- Added changelog automation (#28)
- Changed workflow to use PHP 8.3 (#31)
- Fixed validation bug (#42)
```

This helps users find more context in PR history.
