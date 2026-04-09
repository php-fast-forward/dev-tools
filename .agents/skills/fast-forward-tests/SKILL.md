---
name: fast-forward-tests
description: Generate, extend, and repair PHP unit tests with PHPUnit and Prophecy while respecting PSR-12, Composer PHP constraints, project namespaces, and local testing abstractions. Use when Codex needs to create or update PHPUnit test classes, add coverage attributes, isolate dependencies with Prophecy, infer namespaces from composer.json, reuse abstract test cases or data providers, or run focused commands to validate new PHP tests in an existing codebase.
---

# Fast Forward Tests

## Overview

Generate PHPUnit tests that match the active repository before introducing new patterns. Optimize for clear English test code, precise assertions, Prophecy-based isolation, and focused execution of the tests you create.

## Apply This Workflow

1. Read `composer.json` before writing tests.
2. Determine the active PHP target from `composer.json`. If neither exists, assume the latest stable PHP features that still fit the repository style.
3. Determine namespaces from `autoload.psr-4` and `autoload-dev.psr-4`.
4. Inspect existing tests before generating new ones to discover local abstractions, attribute usage, runner commands, fixtures, helper assertions, and naming patterns.
5. On the first activation in a repository, spend extra effort mapping the testing conventions. Do not edit existing tests unless the user explicitly asks or the tests you touched must be fixed to complete the task.
6. After discovering the repository PHP target, keep every generated test compatible with that version. If the target version changes during the task, rewrite the affected test code to comply with the new constraint.
7. After editing, run the smallest relevant test command and do not treat failing new tests as finished work.

## Project Discovery

On first use in a repository, survey the suite before generating code.

- Read `composer.json`, `phpunit.xml*`, relevant source files, sibling tests, and any obvious runner definitions in composer scripts, `Makefile`, or CI files.
- Search for local use of `#[Test]`, `#[CoversClass]`, `#[UsesClass]`, `#[UsesTrait]`, `#[UsesFunction]`, `#[CoversFunction]`, `ProphecyTrait`, `ObjectProphecy`, `$this->prophesize()`, and `Argument::*`.
- Look for abstract `*TestCase` classes, reusable helper assertions, fixture builders, and data providers.
- Prefer the patterns already established by the repository over generic boilerplate unless the user explicitly asks to modernize the suite.

Read [references/project-discovery.md](references/project-discovery.md) for the discovery checklist.

## Writing Rules

- Write all generated code in English.
- Follow PSR-12 and the repository's import ordering, visibility, finality, blank-line, and `declare(strict_types=1);` conventions.
- Respect namespaces from `composer.json`. Do not invent namespaces that conflict with the PSR-4 mappings.
- Prefer one subject under test per test class unless the local suite intentionally groups multiple collaborators.
- Name test methods descriptively, for example `executeWithInvalidInputWillReturnFailure` or `createWithDefaultsWillReturnInstance`.
- Use `setUp()` for shared dependencies and fixture state. Keep setup inline when it makes the test intent easier to read.
- Use data providers for repeated permutations that share the same assertion shape.
- Extract helper methods only when logic genuinely repeats or when the repository already favors dedicated assertion helpers.
- Use inheritance when an existing abstract test base already captures stable setup or shared assertions for a family of tests.
- Do not add comments, docblocks, or narrative prose inside generated tests unless the user asks for them or the repository clearly requires them for file headers or static-analysis-only generic typing.

## PHPUnit Attributes

Prefer PHPUnit attributes over docblock annotations when the installed PHPUnit version supports them.

- Add `#[Test]` to every test method.
- Add the narrowest relevant coverage metadata based on the repository style.
- Use `#[CoversClass(TestedClass::class)]` for class-level coverage.
- Use `#[UsesClass(UsedClass::class)]`, `#[UsesTrait(UsedTrait::class)]`, and `#[UsesFunction('function_name')]` when collaborators are executed indirectly and the repository tracks them explicitly.
- Use function or method coverage attributes only when they are available in the installed PHPUnit version or already appear in the repository.
- If the repository is constrained to an older PHPUnit version without attribute support, stay compatible and explain the limitation briefly instead of generating unsupported syntax.

## Prophecy Rules

- Use Prophecy when the repository depends on `phpspec/prophecy`, `phpspec/prophecy-phpunit`, or existing tests already use it.
- Prefer `ProphecyTrait` with `$this->prophesize()` unless the repository already uses another Prophecy entry point.
- Always call `reveal()` before passing a prophecy to the system under test or to collaborators that expect the realized double.
- Use `ObjectProphecy` for stored prophecies. Add generic `@var ObjectProphecy<Foo>` docblocks when not present.
- Use focused matchers such as `Argument::type()`, `Argument::that()`, `Argument::any()`, `Argument::containingString()`, and `Argument::cetera()` when values are dynamic.
- Keep expectations precise. Avoid broad matchers when a specific value or type is known.
- Do not overmock pure value objects, small immutable DTOs, or collaborators that are clearer as real instances.

## Assertions and Reuse

- Prefer the most specific PHPUnit assertion available, such as `assertSame`, `assertFalse`, `assertCount`, `assertInstanceOf`, `assertContains`, or `assertEqualsCanonicalizing`.
- Assert behavior, returned values, state transitions, and interactions that matter to the contract.
- Keep one primary reason for failure per test when practical.
- Reuse helper assertions for repeated domain-specific verification such as tracking arrays, normalized payloads, emitted events, or structured command lines.
- When testing a family of classes with a common parent or interface, share reusable setup and assertions through an abstract test case instead of cloning logic.

## Test Execution

Choose the smallest command that proves the change.

- If `fast-forward/dev-tools` appears as a Composer dependency, run `composer dev-tools tests --coverage=public/coverage --filter=TestClassName`.
- Otherwise prefer a project-defined composer script for tests.
- If there is no suitable script, run the repository's direct PHPUnit command with a class or file filter.
- Always run the newly created or modified tests.
- Re-run after fixes until the targeted tests pass.
- If failures come from unrelated pre-existing breakage, call that out separately and do not silently claim success.

Read [references/generation-checklist.md](references/generation-checklist.md) before writing or repairing tests in an unfamiliar repository.