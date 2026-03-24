
Implements the lifecycle of the Composer dev-tools extension framework.

This plugin class MUST initialize and coordinate custom script registrations securely.

***

* Full name: `\FastForward\DevTools\Composer\Plugin`
* This class is marked as **final** and can't be subclassed
* This class implements:
  `Capable`,
  `PluginInterface`
* This class is a **Final class**

## Methods

### getCapabilities

Resolves the implemented Composer capabilities structure.

```php
public getCapabilities(): array<string,string>
```

This method MUST map the primary capability handlers to custom implementations.
It SHALL describe how tools seamlessly integrate into the execution layer.

**Return Value:**

the capability mapping configurations

***

### activate

Handles activation lifecycle events for the Composer session.

```php
public activate(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void
```

The method MUST ensure the `dev-tools` script capability exists inside `composer.json` extras.
It SHOULD append it if currently missing.

**Parameters:**

| Parameter   | Type                         | Description                                              |
|-------------|------------------------------|----------------------------------------------------------|
| `$composer` | **\Composer\Composer**       | the primary package configuration instance over Composer |
| `$io`       | **\Composer\IO\IOInterface** | interactive communication channels                       |

***

### deactivate

Cleans up operations during Composer plugin deactivation events.

```php
public deactivate(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void
```

This method MUST implement the standard Composer lifecycle correctly, even if vacant.

**Parameters:**

| Parameter   | Type                         | Description                            |
|-------------|------------------------------|----------------------------------------|
| `$composer` | **\Composer\Composer**       | the primary metadata controller object |
| `$io`       | **\Composer\IO\IOInterface** | defined interactions proxy             |

***

### uninstall

Handles final uninstallation processes logically.

```php
public uninstall(\Composer\Composer $composer, \Composer\IO\IOInterface $io): void
```

This method MUST manage cleanup duties per Composer constraints, even if empty.

**Parameters:**

| Parameter   | Type                         | Description                                          |
|-------------|------------------------------|------------------------------------------------------|
| `$composer` | **\Composer\Composer**       | system package registry utility                      |
| `$io`       | **\Composer\IO\IOInterface** | execution runtime outputs and inputs proxy interface |

***
