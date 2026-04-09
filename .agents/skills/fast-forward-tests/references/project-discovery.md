# Project Discovery Checklist

## Read First

- `composer.json`
- `phpunit.xml`, `phpunit.xml.dist`, or equivalent PHPUnit config
- the source file being tested
- one to three nearby tests in the same module or namespace
- runner definitions in composer scripts, `Makefile`, and CI files if the test command is not obvious

## Answer These Questions

1. Which PHP version must the generated test support?
2. Which production and test namespaces are defined in Composer PSR-4 mappings?
3. Does the repository already use PHPUnit attributes, annotations, or a mix of both?
4. Does the repository already use Prophecy and, if so, which entry point and matcher style?
5. Is there an abstract `*TestCase`, reusable fixture builder, or assertion helper that should be reused?
6. Does the suite prefer `final` test classes, `declare(strict_types=1);`, file headers, or generic `ObjectProphecy` docblocks?
7. Which command should run only the tests you are changing?

## Useful Survey Commands

```bash
rg --files composer.json phpunit.xml phpunit.xml.dist tests src .github
rg -n '"php"|autoload|autoload-dev|phpunit/phpunit|phpspec/prophecy|fast-forward/dev-tools' composer.json
rg -n '#\\[(Test|CoversClass|CoversFunction|UsesClass|UsesFunction|UsesTrait)' tests
rg -n 'ProphecyTrait|prophesize\\(|ObjectProphecy|Argument::' tests
rg -n 'extends .*TestCase|abstract class .*TestCase' tests
rg -n '"test"|phpunit' composer.json .github/workflows Makefile
```

## Discovery Outcome

Do not start generating code until you know:

- the target namespace
- the repository PHP constraint
- whether Prophecy is in scope
- whether a shared base test case exists
- which focused command will validate the new test