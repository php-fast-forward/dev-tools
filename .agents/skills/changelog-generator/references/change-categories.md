# Change Categories

## Classification by Diff Analysis

Infer category from code patterns, NOT from commit messages.

## Added

**Patterns:**
- New PHP class files
- New methods in existing classes
- New configuration options
- New CLI commands
- New public APIs
- New workflows or automation files
- New user-visible documentation pages that introduce a capability

**Examples:**
- `+class Bootstrapper` → "Added `Bootstrapper` class for changelog bootstrapping"
- `+public function render()` → "Added `MarkdownRenderer::render()` method"
- `+->addOption()` → "Added `--output` option to command"

## Changed

**Patterns:**
- Modified method signatures
- Changed default values
- Behavior modifications
- Refactors that affect the public API
- Workflow or release process changes that alter contributor expectations

**Examples:**
- `function foo($bar)` → `function foo($bar, $baz = null)` → "Changed `foo()` to accept optional `$baz` parameter"
- `return void` → `return string` → "Changed `render()` to return string instead of void"

## Fixed

**Patterns:**
- Bug fixes
- Validation improvements
- Edge case handling
- Error handling corrections
- Broken automation or CI repairs

**Examples:**
- Empty input validation, null checks → "Fixed handling of null input in `parse()`"
- Regex fixes → "Fixed validation of version numbers"

## Removed

**Patterns:**
- Deleted classes
- Deleted methods
- Deleted configuration options
- Removed commands or workflows

**Examples:**
- `-class LegacyParser` → "Removed deprecated `LegacyParser` class"
- `-function oldMethod()` → "Removed deprecated `oldMethod()` method"

## Deprecated

**Patterns:**
- @deprecated annotations
- Deprecation notices in code
- Migration warnings that keep the old surface available for now

**Examples:**
- `@deprecated` → "Deprecated `LegacyParser`, use `MarkdownParser` instead"

## Security

**Patterns:**
- Security patches
- Vulnerability fixes
- Input sanitization
- Permission hardening or secret-handling fixes

**Examples:**
- XSS fixes → "Fixed XSS vulnerability in user input"
- CSRF protection → "Added CSRF protection to form handling"

## Tie-breakers

When a change could fit multiple categories, prefer the most specific outcome:

1. `Security` over every other category
2. `Removed` when the old surface is actually gone
3. `Deprecated` when the old surface still exists but has a migration path
4. `Fixed` for bug repairs, even if files or methods were added to implement the fix
5. `Added` for genuinely new capability
6. `Changed` as the fallback for user-visible behavior shifts

## Skip or compress

- Skip purely internal renames, file moves, or test-only churn unless they change behavior or contributor workflow.
- Compress multiple file edits into one entry when they describe the same visible outcome.
