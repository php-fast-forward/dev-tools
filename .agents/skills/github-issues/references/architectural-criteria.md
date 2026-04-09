# Acceptance Criteria Blocks

Use exactly one base block in the final issue, then append only the variant bullets that are relevant.

## Base Block for Code Changes

```markdown
### Architectural / Isolation Criteria

- **MUST**: The core logic MUST be isolated into dedicated classes or services instead of living inside command or controller entrypoints.
- **MUST**: Responsibilities MUST be separated across input resolution, domain logic, processing or transformation, and output rendering when the change is non-trivial.
- **MUST**: The command or controller layer MUST act only as an orchestrator.
- **MUST**: The implementation MUST avoid tight coupling between core behavior and CLI or framework-specific I/O.
- **MUST**: The design MUST allow future extraction or reuse with minimal changes.
- **MUST**: The solution MUST remain extensible without requiring major refactoring for adjacent use cases.
```

## Base Block for Documentation or Content Work

```markdown
### Documentation / Content Criteria

- **MUST**: The affected documentation or content MUST follow the repository's existing information architecture and naming conventions.
- **MUST**: Related navigation, cross-links, or synchronized outputs MUST be updated when the change affects them.
- **MUST**: Examples, commands, and file paths MUST remain accurate for the current repository behavior.
- **MUST**: The update MUST avoid duplicating content that should live in a single canonical location.
```

## CLI Add-on Bullets

Append these bullets to the code-change block when the request affects a command or console workflow.

```markdown
- **MUST**: Argument and option resolution MUST be validated separately from command execution logic.
- **MUST**: Console formatting and rendering MUST stay separate from domain processing.
- **MUST**: Exit behavior, error messaging, and generated output MUST remain deterministic and testable.
```

## Generation / Report Add-on Bullets

Append these bullets when the issue affects docs generation, reports, wiki sync, or other produced artifacts.

```markdown
- **MUST**: Data gathering or transformation MUST be isolated from filesystem writes or publishing steps.
- **MUST**: Generated output ordering and formatting MUST remain deterministic across runs.
- **MUST**: Re-running the workflow MUST be idempotent or clearly bounded in its side effects.
```

## Testing Expectations

When relevant, mention these expectations in functional criteria rather than repeating them in the architecture block:

- New behavior is covered by focused tests.
- Existing workflows remain compatible.
- Generated outputs or synchronized artifacts are verified where appropriate.
