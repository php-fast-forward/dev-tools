
Provides automated refactoring to prepend basic PHPDoc comments on classes missing them.

This rule MUST adhere to AST standards and SHALL traverse `Class_` nodes exclusively.

***

* Full name: `\FastForward\DevTools\Rector\AddMissingClassPhpDocRector`
* Parent class: [`AbstractRector`](../../../Rector/Rector/AbstractRector)
* This class is marked as **final** and can't be subclassed
* This class is a **Final class**

## Methods

### getRuleDefinition

Resolves the definition describing this rule for documentation generation.

```php
public getRuleDefinition(): \Symplify\RuleDocGenerator\ValueObject\RuleDefinition
```

The method MUST return a properly instantiated RuleDefinition stating its purpose.

**Return Value:**

the description entity for the given Rector rule

***

### getNodeTypes

Declares the types of Abstract Syntax Tree nodes that trigger this refactoring run.

```php
public getNodeTypes(): array<int,class-string<\PhpParser\Node>>
```

The method MUST identify `Class_` nodes reliably. It SHALL define the interception target.

**Return Value:**

an array containing registered node class references

***

### refactor

Triggers the modification process against a matched AST node.

```php
public refactor(\PhpParser\Node $node): \PhpParser\Node|null
```

The method MUST verify the absence of an existing PHPDoc header accurately.
It SHOULD append a basic boilerplate PHPDoc comment if applicable.
If the node is unchanged, it SHALL return null.

**Parameters:**

| Parameter | Type                | Description                                                |
|-----------|---------------------|------------------------------------------------------------|
| `$node`   | **\PhpParser\Node** | the current active syntax instance parsed by the framework |

**Return Value:**

the modified active syntax state, or null if untouched

***
