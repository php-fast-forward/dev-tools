---
name: phpdoc-code-style
description: Reformat Fast Forward PHP source and tests to match repository PHP style and PHPDoc conventions while preserving behavior and public APIs. Use when an agent needs to clean up PHP formatting, add or repair English PHPDoc, satisfy `composer dev-tools phpdoc`, standardize file headers or contract wording, or prepare PHP changes for a Fast Forward pull request. Pair with `github-pull-request` when the PHPDoc cleanup is part of a larger implementation branch.
---

# Fast Forward PHPDoc Code Style

Normalize PHP files for Fast Forward repositories without changing runtime behavior. Prefer evidence-based documentation, repository vocabulary, and the smallest safe edit that makes the PHPDoc and style checks clearer and more credible.

## Workflow

1. Read the target file or snippet plus nearby symbols to capture local conventions. Read [references/checklist.md](references/checklist.md) and [references/fast-forward-conventions.md](references/fast-forward-conventions.md).
2. Preserve runtime behavior, public API names, member order, namespaces, imports, and compatibility boundaries.
3. Normalize formatting and rewrite or add PHPDoc only where it improves correctness or satisfies repository conventions.
4. Before documenting interfaces, abstract contracts, commands, or orchestration services, read [references/anti-patterns.md](references/anti-patterns.md).
5. Use [references/examples.md](references/examples.md) and [references/interface-contract-example.md](references/interface-contract-example.md) when the surrounding code does not provide enough style guidance.
6. Verify with the smallest relevant command. For repository work, prefer `composer dev-tools phpdoc` and broaden validation only when the touched files require it.

## Fast Forward Defaults

- Preserve `declare(strict_types=1);` and the repository file-header block when present.
- Keep PHPDoc in English.
- Keep the repository's left-aligned tag layout and existing vocabulary.
- Treat the file header as the canonical place for repository-wide license and RFC references when the file already uses that pattern.
- Use RFC 2119 keywords conservatively and only where the visible contract supports them.
- Prefer docblocks over speculative signature changes when types are uncertain.
- When PHPDoc work is part of an issue branch, also iterate `github-pull-request` so the PR flow explicitly considers this cleanup.

## Output Contract

- For repository tasks, edit files in place and report what you verified.
- For snippet-only requests, return one fenced `php` code block.
- Prepend one short warning sentence only when syntax errors, missing dependencies, or compatibility risks block a fully safe rewrite.

## Reference Guide

| Need | Reference |
|------|-----------|
| Full transformation checklist and evidence rules | [references/checklist.md](references/checklist.md) |
| Fast Forward file-header and verification conventions | [references/fast-forward-conventions.md](references/fast-forward-conventions.md) |
| Guardrails for interfaces and service contracts | [references/anti-patterns.md](references/anti-patterns.md) |
| Compact formatting and docblock examples | [references/examples.md](references/examples.md) |
| Library-style interface example in Fast Forward vocabulary | [references/interface-contract-example.md](references/interface-contract-example.md) |

## Anti-patterns

- Do not change executable behavior to make the PHPDoc read better.
- Do not add speculative types, exceptions, or lifecycle guarantees.
- Do not duplicate file-header boilerplate inside every symbol docblock.
- Do not force RFC 2119 wording into symbols whose contracts are obvious or weakly evidenced.
- Do not skip repository verification when editing tracked files.
