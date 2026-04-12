---
name: changelog-generator
description: Generate and maintain CHANGELOG.md following Keep a Changelog format with human-readable descriptions. Use when: (1) Creating initial changelog from git tags, (2) Updating changelog for new releases, (3) Generating unreleased section for pull requests. Rule: NEVER use commit messages as source of truth - analyze code diffs instead.
---

# Changelog Generator

Generates and maintains CHANGELOG.md following the Keep a Changelog format with clear, specific, and self-sufficient descriptions.

## Dependencies

- `phly/keep-a-changelog` - Installed in project
- Git - For analyzing code changes
- GitHub CLI (`gh`) - For reading PR context
- Filesystem - For reading/writing CHANGELOG.md

## PR Context Integration

When generating changelog for changes that belong to a PR:

1. Detect PR reference: Check git branch name or recent PR comments
2. Fetch PR description: Use `gh pr view <pr-number> --json body`
3. Extract context: Read the PR Summary and Changes sections
4. Enhance descriptions: Use PR context to write more accurate changelog entries

Example workflow:
```bash
# Detect current PR
BRANCH=$(git branch --show-current)
PR_NUM=$(echo "$BRANCH" | grep -oE '[0-9]+' | head -1)

# Fetch PR context if exists
if [ -n "$PR_NUM" ]; then
  PR_BODY=$(gh pr view "$PR_NUM" --json body --jq '.body')
  # Use PR body as an extra context to define the changelog descriptions
fi
```

This ensures changelog descriptions align with the PR intent and provide better context.

## Key Commands

```bash
vendor/bin/changelog             # Main CLI
vendor/bin/changelog add:entry   # Add entry to version
vendor/bin/changelog release    # Create release
```

## Execution Pipeline (Deterministic)

### Stage 1: Initial State

1. Check if CHANGELOG.md exists and has content:
   ```bash
   ls -la CHANGELOG.md 2>/dev/null || echo "NO_FILE"
   ```

### Stage 2: Version Discovery

1. List all tags sorted semantically:
   ```bash
   git tag --sort=-version:refname
   ```

2. Identify:
   - Last documented version in CHANGELOG
   - Tags not yet documented

### Stage 3: Historical Content Generation

**Case A: No CHANGELOG or Empty**

For each tag (ascending order):
1. Calculate diff between current tag and previous tag (or first commit for initial version)
2. Analyze code diff to infer changes (NOT commit messages)
3. Group changes by type (Added, Changed, Fixed, Removed, Deprecated, Security)
4. Insert version section

**B: Existing CHANGELOG**

1. Identify last documented version
2. For each subsequent tag:
   - Generate diff between versions
   - Insert new section in changelog

### Stage 4: Unreleased Section

1. Calculate diff between last documented tag and HEAD
2. Generate [Unreleased] section with current changes

## Change Classification (Inferred from Diff)

Analyze actual code changes, NOT commit messages:

| Pattern | Category |
|---------|----------|
| New files, new classes, new methods | Added |
| Behavior changes, refactors, signature changes | Changed |
| Bug fixes, validation fixes | Fixed |
| Deleted classes, removed methods | Removed |
| @deprecated markers | Deprecated |
| Security patches | Security |

## Quality Rules

- **SHORT**: One line per change
- **SPECIFIC**: Include class/method names
- **SELF-SUFFICIENT**: Understand without reading code
- **FUNCTIONAL**: Describe impact, not implementation
- **PR-AWARE**: Use PR description to enhance accuracy when available

Good: "Added `Bootstrapper::bootstrap()` to create CHANGELOG.md when missing"
Bad: "Add bootstrap command"

## PR Context Usage

When a PR number is available:

1. Read PR description for implementation intent
2. Extract key capabilities mentioned in Summary
3. Use specific feature names from the PR to write accurate descriptions
4. Reference PR in changelog: "Added changelog automation (#40)"

## Integration with keep-a-changelog

Use CLI commands when possible:

```bash
# Add unreleased entry
vendor/bin/changelog add:entry --unreleased --type=added "Description"

# Add release entry
vendor/bin/changelog add:entry 1.0.0 --type=added "Description"

# Create release
vendor/bin/changelog release 1.0.0 --date="2026-04-11"
```

Edit CHANGELOG.md directly if CLI insufficient.

## Verification

Valid changelog MUST have:
- All sections: Added, Changed, Deprecated, Removed, Fixed, Security
- No "Nothing." placeholders (unless truly empty)
- Reverse chronological order (newest first)
- [Unreleased] at top when applicable

## Reference Files

- [references/keep-a-changelog-format.md](references/keep-a-changelog-format.md) - Format spec
- [references/change-categories.md](references/change-categories.md) - Classification guide
- [references/description-patterns.md](references/description-patterns.md) - Human-readable patterns
