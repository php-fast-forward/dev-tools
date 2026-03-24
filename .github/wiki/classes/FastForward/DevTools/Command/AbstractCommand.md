
Provides a base configuration and common utilities for Composer commands.

Extending classes MUST rely on this base abstraction to interact with the console
application gracefully, and SHALL adhere to the expected return types for commands.

***

* Full name: `\FastForward\DevTools\Command\AbstractCommand`
* Parent class: [`BaseCommand`](../../../Composer/Command/BaseCommand)
* This class is an **Abstract class**

## Properties

### filesystem

```php
protected \Symfony\Component\Filesystem\Filesystem $filesystem
```

***

## Methods

### __construct

Constructs a new AbstractCommand instance.

```php
public __construct(\Symfony\Component\Filesystem\Filesystem|null $filesystem = null): mixed
```

The method MAY accept a Filesystem instance; if omitted, it SHALL instantiate a new one.

**Parameters:**

| Parameter     | Type                                               | Description                   |
|---------------|----------------------------------------------------|-------------------------------|
| `$filesystem` | **\Symfony\Component\Filesystem\Filesystem\|null** | the filesystem utility to use |

***

### runProcess

Executes a given system process gracefully and outputs its buffer.

```php
protected runProcess(\Symfony\Component\Process\Process $command, \Symfony\Component\Console\Output\OutputInterface $output): int
```

The method MUST execute the provided command ensuring the output is channeled
to the OutputInterface. It SHOULD leverage TTY if supported. If the process
fails, it MUST return `self::FAILURE`; otherwise, it SHALL return `self::SUCCESS`.

**Parameters:**

| Parameter  | Type                                                  | Description                                     |
|------------|-------------------------------------------------------|-------------------------------------------------|
| `$command` | **\Symfony\Component\Process\Process**                | the configured process instance to run          |
| `$output`  | **\Symfony\Component\Console\Output\OutputInterface** | the output interface to log warnings or results |

**Return Value:**

the status code of the command execution

***

### getCurrentWorkingDirectory

Retrieves the current working directory of the application.

```php
protected getCurrentWorkingDirectory(): string
```

The method MUST return the initial working directory defined by the application.
If not available, it SHALL fall back to the safe current working directory.

**Return Value:**

the absolute path to the current working directory

***

### getAbsolutePath

Computes the absolute path for a given relative or absolute path.

```php
protected getAbsolutePath(string $relativePath): string
```

This method MUST return the exact path if it is already absolute.
If relative, it SHALL make it absolute relying on the current working directory.

**Parameters:**

| Parameter       | Type       | Description                     |
|-----------------|------------|---------------------------------|
| `$relativePath` | **string** | the path to evaluate or resolve |

**Return Value:**

the resolved absolute path

***

### getConfigFile

Determines the correct absolute path to a configuration file.

```php
protected getConfigFile(string $filename, bool $force = false): string
```

The method MUST attempt to resolve the configuration file locally in the working directory.
If absent and not forced, it SHALL provide the default equivalent from the package itself.

**Parameters:**

| Parameter   | Type       | Description                                                                     |
|-------------|------------|---------------------------------------------------------------------------------|
| `$filename` | **string** | the name of the configuration file                                              |
| `$force`    | **bool**   | determines whether to bypass fallback and forcefully return the local file path |

**Return Value:**

the resolved absolute path to the configuration file

***

### runCommand

Configures and executes a registered console command by name.

```php
protected runCommand(string $commandName, array|\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
```

The method MUST look up the command from the application and run it. It SHALL ignore generic
validation errors and route the custom input and output correctly.

**Parameters:**

| Parameter      | Type                                                       | Description                             |
|----------------|------------------------------------------------------------|-----------------------------------------|
| `$commandName` | **string**                                                 | the name of the required command        |
| `$input`       | **array\|\Symfony\Component\Console\Input\InputInterface** | the input arguments or array definition |
| `$output`      | **\Symfony\Component\Console\Output\OutputInterface**      | the interface for buffering output      |

**Return Value:**

the status code resulting from the dispatched command

***

### getPsr4Namespaces

Retrieves configured PSR-4 namespaces from the composer configuration.

```php
protected getPsr4Namespaces(): array
```

This method SHALL parse the underlying `composer.json` using the Composer instance,
and MUST provide an empty array if no specific paths exist.

**Return Value:**

the PSR-4 namespaces mappings

***

### getTitle

Computes the human-readable title or description of the current application.

```php
protected getTitle(): string
```

The method SHOULD utilize the package description as the title, but MUST provide
the raw package name as a fallback mechanism.

**Return Value:**

the computed title or description string

***
