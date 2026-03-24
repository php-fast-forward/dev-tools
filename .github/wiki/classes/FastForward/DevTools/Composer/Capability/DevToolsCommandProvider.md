
Provides a registry of custom dev-tools commands mapped for Composer integration.

This capability struct MUST implement the defined `CommandProviderCapability`.

***

* Full name: `\FastForward\DevTools\Composer\Capability\DevToolsCommandProvider`
* This class is marked as **final** and can't be subclassed
* This class implements:
  `CommandProvider`
* This class is a **Final class**

## Methods

### getCommands

Dispatches the comprehensive collection of CLI commands.

```php
public getCommands(): array<int,\FastForward\DevTools\Command\AbstractCommand>
```

The method MUST yield an array of instantiated command classes representing the tools.
It SHALL be queried by the Composer plugin dynamically during runtime execution.

**Return Value:**

the commands defined within the toolset

***
