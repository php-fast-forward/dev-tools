
Wraps the fast-forward console tooling suite conceptually as an isolated application instance.

Extending the base application, it MUST provide default command injections safely.

***

* Full name: `\FastForward\DevTools\DevTools`
* Parent class: [`Application`](../../Composer/Console/Application)
* This class is marked as **final** and can't be subclassed
* This class is a **Final class**

## Properties

### commandProvider

```php
private ?\Composer\Plugin\Capability\CommandProvider $commandProvider
```

***

## Methods

### __construct

Initializes the DevTools global context and dependency graph.

```php
public __construct(\Composer\Plugin\Capability\CommandProvider|null $commandProvider = new \FastForward\DevTools\Composer\Capability\DevToolsCommandProvider()): mixed
```

The method MUST define default configurations and MAY accept an explicit command provider.
It SHALL instruct the runner to treat the `standards` command generically as its default endpoint.

**Parameters:**

| Parameter          | Type                                                  | Description                                                      |
|--------------------|-------------------------------------------------------|------------------------------------------------------------------|
| `$commandProvider` | **\Composer\Plugin\Capability\CommandProvider\|null** | provides the execution references securely, defaults dynamically |

***

### getDefaultCommands

Aggregates default processes attached safely to the environment base lifecycle.

```php
protected getDefaultCommands(): array<int,mixed>
```

The method MUST inject core operational constraints and external definitions seamlessly.
It SHALL execute an overriding merge logically combining provider and utility features.

**Return Value:**

the collected list of functional commands configured to run

***
