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

**Examples:**
- `function foo($bar)` → `function foo($bar, $baz = null)` → "Changed `foo()` to accept optional `$baz` parameter"
- `return void` → `return string` → "Changed `render()` to return string instead of void"

## Fixed

**Patterns:**
- Bug fixes
- Validation improvements
- Edge case handling
- Error handling corrections

**Examples:**
- Empty input validation, null checks → "Fixed handling of null input in `parse()`"
- Regex fixes → "Fixed validation of version numbers"

## Removed

**Patterns:**
- Deleted classes
- Deleted methods
- Deleted configuration options

**Examples:**
- `-class LegacyParser` → "Removed deprecated `LegacyParser` class"
- `-function oldMethod()` → "Removed deprecated `oldMethod()` method"

## Deprecated

**Patterns:**
- @deprecated annotations
- Deprecation notices in code

**Examples:**
- `@deprecated` → "Deprecated `LegacyParser`, use `MarkdownParser` instead"

## Security

**Patterns:**
- Security patches
- Vulnerability fixes
- Input sanitization

**Examples:**
- XSS fixes → "Fixed XSS vulnerability in user input"
- CSRF protection → "Added CSRF protection to form handling"