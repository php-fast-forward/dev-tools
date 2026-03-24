
Executes AST inspections parsing missing documentation on methods automatically.

It MUST append `@param`, `@return`, and `@throws` tags where deduced accurately.
The logic SHALL NOT override existing documentation.

***

* Full name: `\FastForward\DevTools\Rector\AddMissingMethodPhpDocRector`
* Parent class: [`AbstractRector`](../../../Rector/Rector/AbstractRector)
* This class is marked as **final** and can't be subclassed
* This class is a **Final class**

## Methods

### getRuleDefinition

Delivers the formal rule description configured within the Rector ecosystem.

```php
public getRuleDefinition(): \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
```

The method MUST accurately describe its functional changes logically.

**Return Value:**

explains the rule's active behavior context

***

### getNodeTypes

Designates the primary Abstract Syntax Tree (AST) node structures intercepted.

```php
public getNodeTypes(): array<int,class-string<\PhpParser\Node>>
```

The method MUST register solely `ClassMethod` class references to guarantee precision.

**Return Value:**

the structural bindings applicable for this modification

***

### refactor

Computes necessary PHPDoc metadata for a given class method selectively.

```php
public refactor(\PhpParser\Node $node): \PhpParser\Node
```

The method MUST identify the missing `@param`, `@return`, and `@throws` tags algorithmically.
It SHALL preserve pre-existing valid tags cleanly. If no augmentation is achieved, it returns the node unaltered.

**Parameters:**

| Parameter | Type                | Description                                           |
|-----------|---------------------|-------------------------------------------------------|
| `$node`   | **\PhpParser\Node** | the target method representation parsed synchronously |

**Return Value:**

the refined active syntax instance inclusive of generated documentation

***

### normalizeDocblockSpacing

Formats the newly synthesized document block optimally, balancing whitespaces and gaps.

```php
private normalizeDocblockSpacing(string $docblock): string
```

This method MUST ensure visual spacing between separate tag families (e.g., between param and return).
It SHALL preserve the structural integrity of the PHPDoc format effectively.

**Parameters:**

| Parameter   | Type       | Description                                                 |
|-------------|------------|-------------------------------------------------------------|
| `$docblock` | **string** | the unsanitized raw string equivalent of the document block |

**Return Value:**

the formatted textual content accurately respecting conventions

***

### resolveTagGroup

Attempts to resolve the functional category inherent to a documentation tag.

```php
private resolveTagGroup(string $line): string|null
```

The method MUST parse the string descriptor reliably, extracting the tag intention logically.

**Parameters:**

| Parameter | Type       | Description                                           |
|-----------|------------|-------------------------------------------------------|
| `$line`   | **string** | the single document property statement being reviewed |

**Return Value:**

the functional label or null if unbound correctly

***

### shouldInsertBlankLineBetweenTagGroups

Concludes if architectural clarity requires an explicit blank interval.

```php
private shouldInsertBlankLineBetweenTagGroups(string $previousTagGroup, string $currentTagGroup): bool
```

The method MUST mandate proper line spacing between `@param`, `@return`, and `@throws` groups.

**Parameters:**

| Parameter           | Type       | Description                                       |
|---------------------|------------|---------------------------------------------------|
| `$previousTagGroup` | **string** | the prior tag context encountered                 |
| `$currentTagGroup`  | **string** | the newly active tag context currently processing |

**Return Value:**

true if an empty marker requires insertion natively

***

### getExistingParamVariables

Collates variables already declared adequately within the existing documentation base.

```php
private getExistingParamVariables(\phpowermove\docblock\Docblock $docblock): string[]
```

This method MUST retrieve predefined `@param` configurations logically, avoiding collisions.

**Parameters:**

| Parameter   | Type                               | Description                                     |
|-------------|------------------------------------|-------------------------------------------------|
| `$docblock` | **\phpowermove\docblock\Docblock** | the active parsed commentary structure instance |

**Return Value:**

uniquely filtered established parameters

***

### shouldAddReturnTag

Calculates whether a `@return` tag is fundamentally valid for the given context.

```php
private shouldAddReturnTag(\PhpParser\Node\Stmt\ClassMethod $node, \phpowermove\docblock\Docblock $docblock): bool
```

The method SHALL exclude magic implementations such as `__construct` deliberately.

**Parameters:**

| Parameter   | Type                                 | Description                                        |
|-------------|--------------------------------------|----------------------------------------------------|
| `$node`     | **\PhpParser\Node\Stmt\ClassMethod** | the specific operation structure verified securely |
| `$docblock` | **\phpowermove\docblock\Docblock**   | the connected documentation references             |

**Return Value:**

indicates validation explicitly approving return blocks selectively

***

### getExistingThrowsTypes

Assembles all established exceptions logged intentionally within the existing tag array.

```php
private getExistingThrowsTypes(\phpowermove\docblock\Docblock $docblock): string[]
```

The method MUST enumerate declared `@throws` statements efficiently.

**Parameters:**

| Parameter   | Type                               | Description                                        |
|-------------|------------------------------------|----------------------------------------------------|
| `$docblock` | **\phpowermove\docblock\Docblock** | the functional parser tree model internally loaded |

**Return Value:**

discovered types of configured operational errors generically

***

### resolveThrows

Parses the architectural scope of an intercepted method to infer exceptional operations natively.

```php
private resolveThrows(\PhpParser\Node\Stmt\ClassMethod $node): string[]
```

This method MUST accurately deduce exception creations traversing internal components recursively.
It SHALL strictly return precise, unique internal naming identifiers safely.

**Parameters:**

| Parameter | Type                                 | Description                                                       |
|-----------|--------------------------------------|-------------------------------------------------------------------|
| `$node`   | **\PhpParser\Node\Stmt\ClassMethod** | the active evaluated root target element dynamically instantiated |

**Return Value:**

expected failure objects effectively defined within its contextual boundary

***

### resolveNameToString

Expands Name syntax objects into human-readable string descriptors universally.

```php
private resolveNameToString(\PhpParser\Node\Name $name): string
```

The method MUST handle aliases seamlessly or fallback to base names dependably.

**Parameters:**

| Parameter | Type                     | Description                                  |
|-----------|--------------------------|----------------------------------------------|
| `$name`   | **\PhpParser\Node\Name** | the structured reference to parse accurately |

**Return Value:**

the computed class identifier successfully reconstructed

***

### createDocblockFromReflection

Evaluates PHPStan reflection metadata securely deriving original PHPDoc components.

```php
private createDocblockFromReflection(\PhpParser\Node\Stmt\ClassMethod $node): \phpowermove\docblock\Docblock
```

The method SHOULD establish scope accurately and fetch reliable documentation defaults safely.

**Parameters:**

| Parameter | Type                                 | Description                                                   |
|-----------|--------------------------------------|---------------------------------------------------------------|
| `$node`   | **\PhpParser\Node\Stmt\ClassMethod** | the associated target structure explicitly handled internally |

**Return Value:**

the built virtualized docblock reference precisely retrieved natively

***

### resolveTypeToString

Translates complicated type primitives cleanly back into uniform string declarations consistently.

```php
private resolveTypeToString(string|\PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\ComplexType|null $type): string
```

The method MUST parse complex combinations including Intersections, Unions natively and securely.

**Parameters:**

| Parameter | Type                                                                                            | Description                                    |
|-----------|-------------------------------------------------------------------------------------------------|------------------------------------------------|
| `$type`   | **string\|\PhpParser\Node\Identifier\|\PhpParser\Node\Name\|\PhpParser\Node\ComplexType\|null** | the original metadata instance safely captured |

**Return Value:**

the final interpreted designation string explicitly represented safely

***
