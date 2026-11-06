# Generation Checklist

## Before Writing

1. Match the file path and namespace to the repository structure.
2. Confirm the subject under test and the collaborators that should be mocked or kept real.
3. Decide whether an existing abstract test base or helper should be reused.
4. Confirm the narrowest command that can run the target test class or file.

## While Writing

1. Add `declare(strict_types=1);` when the repository uses it.
2. Add `#[Test]` to every test method.
3. Add `#[CoversClass(...)]` and any needed `#[Uses*]` attributes when they are supported and already aligned with the repository style.
4. Use descriptive method names in English.
5. Use `setUp()` only for shared state.
6. Use Prophecy matchers instead of loose expectations for dynamic arguments.
7. Call `reveal()` before passing a prophecy into the system under test.
8. Prefer specific PHPUnit assertions.
9. Use data providers for repeated input and output matrices.
10. Prefer generate random data instead of fixed data for test cases to increase entropy and edge cases. If the data is not random, it should be at least diverse.
11. If faker is available, use it to generate random data.
12. Avoid comments unless the user explicitly asks for them or the repository requires them.

## Before Finishing

1. Run the focused test command.
2. Fix failures in the tests you created or changed.
3. Separate unrelated pre-existing failures from failures caused by your work.
4. Confirm that the final test code still matches the repository PHP version, namespace mapping, and style conventions.
