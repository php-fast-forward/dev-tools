
This is an automatically generated documentation for **Fast Forward Development Tools for PHP projects**.

## Namespaces

### \FastForward\DevTools

#### Classes

| Class                                                 | Description                                                                                    |
|-------------------------------------------------------|------------------------------------------------------------------------------------------------|
| [`DevTools`](./classes/FastForward/DevTools/DevTools) | Wraps the fast-forward console tooling suite conceptually as an isolated application instance. |

### \FastForward\DevTools\Command

#### Classes

| Class                                                                         | Description                                                                             |
|-------------------------------------------------------------------------------|-----------------------------------------------------------------------------------------|
| [`AbstractCommand`](./classes/FastForward/DevTools/Command/AbstractCommand)   | Provides a base configuration and common utilities for Composer commands.               |
| [`CodeStyleCommand`](./classes/FastForward/DevTools/Command/CodeStyleCommand) | Represents the command responsible for checking and fixing code style issues.           |
| [`DocsCommand`](./classes/FastForward/DevTools/Command/DocsCommand)           | Handles the generation of API documentation for the project.                            |
| [`PhpDocCommand`](./classes/FastForward/DevTools/Command/PhpDocCommand)       | Provides operations to inspect, lint, and repair PHPDoc comments across the project.    |
| [`RefactorCommand`](./classes/FastForward/DevTools/Command/RefactorCommand)   | Provides functionality to execute automated code refactoring using Rector.              |
| [`ReportsCommand`](./classes/FastForward/DevTools/Command/ReportsCommand)     | Coordinates the generation of Fast Forward documentation frontpage and related reports. |
| [`StandardsCommand`](./classes/FastForward/DevTools/Command/StandardsCommand) | Executes the full suite of Fast Forward code standard checks.                           |
| [`TestsCommand`](./classes/FastForward/DevTools/Command/TestsCommand)         | Facilitates the execution of the PHPUnit testing framework.                             |

### \FastForward\DevTools\Composer

#### Classes

| Class                                                      | Description                                                             |
|------------------------------------------------------------|-------------------------------------------------------------------------|
| [`Plugin`](./classes/FastForward/DevTools/Composer/Plugin) | Implements the lifecycle of the Composer dev-tools extension framework. |

### \FastForward\DevTools\Composer\Capability

#### Classes

| Class                                                                                                   | Description                                                                       |
|---------------------------------------------------------------------------------------------------------|-----------------------------------------------------------------------------------|
| [`DevToolsCommandProvider`](./classes/FastForward/DevTools/Composer/Capability/DevToolsCommandProvider) | Provides a registry of custom dev-tools commands mapped for Composer integration. |

### \FastForward\DevTools\Rector

#### Classes

| Class                                                                                                | Description                                                                                    |
|------------------------------------------------------------------------------------------------------|------------------------------------------------------------------------------------------------|
| [`AddMissingClassPhpDocRector`](./classes/FastForward/DevTools/Rector/AddMissingClassPhpDocRector)   | Provides automated refactoring to prepend basic PHPDoc comments on classes missing them.       |
| [`AddMissingMethodPhpDocRector`](./classes/FastForward/DevTools/Rector/AddMissingMethodPhpDocRector) | Executes AST inspections parsing missing documentation on methods automatically.               |
| [`RemoveEmptyDocBlockRector`](./classes/FastForward/DevTools/Rector/RemoveEmptyDocBlockRector)       | Implements automation targeting the removal of purposeless empty DocBlock structures natively. |
