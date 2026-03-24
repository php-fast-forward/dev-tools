
Provides operations to inspect, lint, and repair PHPDoc comments across the project.

The class MUST NOT be extended and SHALL coordinate tools like PHP-CS-Fixer and Rector.

***

* Full name: `\FastForward\DevTools\Command\PhpDocCommand`
* Parent class: [`\FastForward\DevTools\Command\AbstractCommand`](./AbstractCommand)
* This class is marked as **final** and can't be subclassed
* This class is a **Final class**

## Constants

| Constant   | Visibility | Type   | Value                    |
|------------|------------|--------|--------------------------|
| `FILENAME` | public     | string | '.docheader'             |
| `CONFIG`   | public     | string | '.php-cs-fixer.dist.php' |

## Methods

### configure

Configures the PHPDoc command.

```php
protected configure(): void
```

This method MUST securely configure the expected inputs, such as the `--fix` option.

***

### execute

Executes the PHPDoc checks and rectifications.

```php
protected execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
```

The method MUST ensure the `.docheader` template is present. It SHALL then invoke
PHP-CS-Fixer and Rector. If both succeed, it MUST return `self::SUCCESS`.

**Parameters:**

| Parameter | Type                                                  | Description                  |
|-----------|-------------------------------------------------------|------------------------------|
| `$input`  | **\Symfony\Component\Console\Input\InputInterface**   | the command input parameters |
| `$output` | **\Symfony\Component\Console\Output\OutputInterface** | the system output handler    |

**Return Value:**

the success or failure state

***

### runPhpCsFixer

Executes the PHP-CS-Fixer checks internally.

```php
private runPhpCsFixer(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
```

The method SHOULD run in dry-run mode unless the fix flag is explicitly provided.
It MUST return an integer describing the exit code.

**Parameters:**

| Parameter | Type                                                  | Description               |
|-----------|-------------------------------------------------------|---------------------------|
| `$input`  | **\Symfony\Component\Console\Input\InputInterface**   | the parsed console inputs |
| `$output` | **\Symfony\Component\Console\Output\OutputInterface** | the configured outputs    |

**Return Value:**

the status result of the underlying process

***

### runRector

Runs Rector to insert missing method block comments automatically.

```php
private runRector(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
```

The method MUST apply the `AddMissingMethodPhpDocRector` constraint locally.
It SHALL strictly return an integer denoting success or failure.

**Parameters:**

| Parameter | Type                                                  | Description                     |
|-----------|-------------------------------------------------------|---------------------------------|
| `$input`  | **\Symfony\Component\Console\Input\InputInterface**   | the incoming console parameters |
| `$output` | **\Symfony\Component\Console\Output\OutputInterface** | the outgoing console display    |

**Return Value:**

the code indicating the process result

***

### ensureDocHeaderExists

Creates the missing document header configuration file if needed.

```php
private ensureDocHeaderExists(\Symfony\Component\Console\Output\OutputInterface $output): void
```

The method MUST query the local filesystem. If the file is missing, it SHOULD copy
the tool template into the root folder.

**Parameters:**

| Parameter | Type                                                  | Description                                         |
|-----------|-------------------------------------------------------|-----------------------------------------------------|
| `$output` | **\Symfony\Component\Console\Output\OutputInterface** | the logger where missing capabilities are announced |

***

## Inherited methods

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
